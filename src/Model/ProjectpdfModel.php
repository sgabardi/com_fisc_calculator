<?php
declare(strict_types=1);

namespace FiscCalculator\Component\Fisc_calculator\Site\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

/**
 * Project model – Cases, Items & Preis-Aggregationen (Median).
 *
 * Preis-/COGS-Daten liegen ausschließlich in #__fisc_sales_data.
 */
class ProjectpdfModel extends BaseDatabaseModel
{
    /** Cache für autodetektierte Produkt-Tabelle */
    private static ?string $productTableCache = null;

    /**
     * Liefert den korrekten Produkt-Tabellennamen (singular/plural) mit #__-Prefix.
     */
    private function productTable(): string
    {
        if (self::$productTableCache !== null) {
            return self::$productTableCache;
        }

        $db = $this->getDbo();
        $candidates = ['#__fisc_product_items', '#__fisc_product_item'];

        foreach ($candidates as $cand) {
            $table = $db->replacePrefix($cand);
            try {
                $db->setQuery('SELECT 1 FROM ' . $db->quoteName($table) . ' LIMIT 1')->execute();
                self::$productTableCache = $cand; // mit #__ für spätere Nutzung in SQL-Strings
                return self::$productTableCache;
            } catch (\Throwable $e) {
                // nächsten Kandidaten testen
            }
        }

        // Fallback (plural)
        self::$productTableCache = '#__fisc_product_items';
        return self::$productTableCache;
    }

    /**
     * Aggregierte Werte (Min / Median / Max / COGS) für ALLE Case-Items eines Projekts.
     * Median wird als mean_price-Alias zurückgegeben (Kompatibilität zum Frontend).
     */
    public function getItems(int $projectId): array
    {
        $db  = $this->getDbo();
        $pid = (int) $projectId;
        $pt  = $this->productTable();

        $sql = "
WITH base AS (
  SELECT DISTINCT
    pi.id    AS product_id,
    pi.ref   AS ref,
    pi.title AS title
  FROM #__fisc_project_case c
  INNER JOIN #__fisc_project_case_item ci ON ci.case_id = c.id
  INNER JOIN {$pt} pi ON pi.id = ci.product_id
  WHERE c.project_id = {$pid}
),
sales AS (
  SELECT
    b.product_id,
    sd.sales_lc,
    /* COGS nur gültig, wenn qty = 1 und cogs_lc > 0 */
    CASE WHEN sd.qty = 1 AND sd.cogs_lc > 0 THEN sd.cogs_lc END AS cogs_lc
  FROM base b
  LEFT JOIN #__fisc_sales_data sd
         ON sd.product_code = (SELECT ref FROM {$pt} p WHERE p.id = b.product_id)
  WHERE sd.sales_lc IS NOT NULL AND sd.sales_lc >= 0
),
ordered AS (
  SELECT
    s.*,
    ROW_NUMBER() OVER (PARTITION BY s.product_id ORDER BY s.sales_lc) AS rn,
    COUNT(*)    OVER (PARTITION BY s.product_id)                       AS cnt
  FROM sales s
),
agg AS (
  SELECT
    product_id,
    MIN(sales_lc) AS min_price,
    MAX(sales_lc) AS max_price,
    AVG(CASE
          WHEN cnt % 2 = 1 AND rn = (cnt + 1) / 2             THEN sales_lc
          WHEN cnt % 2 = 0 AND (rn = cnt/2 OR rn = cnt/2 + 1) THEN sales_lc
        END) AS mean_price,
    /* MAX über gefilterte (ggf. NULL) COGS -> ignoriert ungültige */
    MAX(cogs_lc) AS cogs_lc
  FROM ordered
  GROUP BY product_id
)
SELECT
  b.product_id AS id,
  b.ref,
  b.title,
  COALESCE(a.min_price,  0) AS min_price,
  COALESCE(a.max_price,  0) AS max_price,
  COALESCE(a.mean_price, 0) AS mean_price,
  COALESCE(a.cogs_lc,    0) AS cogs_lc
FROM base b
LEFT JOIN agg a ON a.product_id = b.product_id
ORDER BY b.title ASC
";
        $db->setQuery($sql);
        return $db->loadObjectList() ?: [];
    }

    /**
     * Aggregierte Werte (Min / Median / Max / COGS) für EIN Fall (inkl. Freipreis-Felder).
     * Median wird als mean_price-Alias zurückgegeben (Kompatibilität zum Frontend).
     */
    public function getItemsByCase(int $caseId): array
    {
        $db  = $this->getDbo();
        $cid = (int) $caseId;
        $pt  = $this->productTable();

        $sql = "
WITH base AS (
  SELECT
    ci.id    AS case_item_id,
    pi.id    AS product_id,
    pi.ref   AS ref,
    pi.title AS title,
    ci.free_price1, ci.free_price2, ci.free_price3
  FROM #__fisc_project_case_item ci
  INNER JOIN {$pt} pi ON pi.id = ci.product_id
  WHERE ci.case_id = {$cid}
),
sales AS (
  SELECT
    b.product_id,
    sd.sales_lc,
    /* COGS nur gültig, wenn qty = 1 und cogs_lc > 0 */
    CASE WHEN sd.qty = 1 AND sd.cogs_lc > 0 THEN sd.cogs_lc END AS cogs_lc
  FROM base b
  LEFT JOIN #__fisc_sales_data sd
         ON sd.product_code = (SELECT ref FROM {$pt} p WHERE p.id = b.product_id)
  WHERE sd.sales_lc IS NOT NULL AND sd.sales_lc >= 0
),
ordered AS (
  SELECT
    s.*,
    ROW_NUMBER() OVER (PARTITION BY s.product_id ORDER BY s.sales_lc) AS rn,
    COUNT(*)    OVER (PARTITION BY s.product_id)                       AS cnt
  FROM sales s
),
agg AS (
  SELECT
    product_id,
    MIN(sales_lc) AS min_price,
    MAX(sales_lc) AS max_price,
    AVG(CASE
          WHEN cnt % 2 = 1 AND rn = (cnt + 1) / 2             THEN sales_lc
          WHEN cnt % 2 = 0 AND (rn = cnt/2 OR rn = cnt/2 + 1) THEN sales_lc
        END) AS mean_price,
    MAX(cogs_lc) AS cogs_lc
  FROM ordered
  GROUP BY product_id
)
SELECT
  b.product_id AS id,
  b.ref,
  b.title,
  b.case_item_id,
  b.free_price1, b.free_price2, b.free_price3,
  COALESCE(a.min_price,  0) AS min_price,
  COALESCE(a.max_price,  0) AS max_price,
  COALESCE(a.mean_price, 0) AS mean_price,
  COALESCE(a.cogs_lc,    0) AS cogs_lc
FROM base b
LEFT JOIN agg a ON a.product_id = b.product_id
ORDER BY b.case_item_id ASC
";
        $db->setQuery($sql);
        return $db->loadObjectList() ?: [];
    }

    /** Liste der Cases eines Projekts */
    public function getProjectCases(int $projectId): array
    {
        $db  = $this->getDbo();
        $pid = (int) $projectId;

        $q = $db->getQuery(true)
            ->select(['id','project_id','case_name','created','created_by'])
            ->from($db->quoteName('#__fisc_project_case'))
            ->where($db->quoteName('project_id') . ' = :pid')
            ->bind(':pid', $pid, ParameterType::INTEGER)
            ->order($db->quoteName('id') . ' DESC');

        $db->setQuery($q);
        return $db->loadObjectList() ?: [];
    }

    /** Neues Case anlegen */
    public function createCase(int $projectId, string $caseName): int
    {
        $db = $this->getDbo();

        $q = $db->getQuery(true)
            ->insert($db->quoteName('#__fisc_project_case'))
            ->columns([$db->quoteName('project_id'), $db->quoteName('case_name'), $db->quoteName('created_by')])
            ->values(implode(',', [
                (int) $projectId,
                $db->quote($caseName),
                (int) Factory::getUser()->id,
            ]));

        $db->setQuery($q)->execute();
        return (int) $db->insertid();
    }

    /** Case umbenennen */
    public function updateCaseName(int $caseId, string $newName): bool
    {
        $db  = $this->getDbo();
        $cid = (int) $caseId;

        $q = $db->getQuery(true)
            ->update($db->quoteName('#__fisc_project_case'))
            ->set($db->quoteName('case_name') . ' = ' . $db->quote($newName))
            ->where($db->quoteName('id') . ' = :cid')
            ->bind(':cid', $cid, ParameterType::INTEGER);

        $db->setQuery($q)->execute();
        return true;
    }

    /** Case löschen (inkl. Items) */
    public function deleteCase(int $caseId): bool
    {
        $db  = $this->getDbo();
        $cid = (int) $caseId;

        $q1 = $db->getQuery(true)
            ->delete($db->quoteName('#__fisc_project_case_item'))
            ->where($db->quoteName('case_id') . ' = :cid1')
            ->bind(':cid1', $cid, ParameterType::INTEGER);
        $db->setQuery($q1)->execute();

        $q2 = $db->getQuery(true)
            ->delete($db->quoteName('#__fisc_project_case'))
            ->where($db->quoteName('id') . ' = :cid2')
            ->bind(':cid2', $cid, ParameterType::INTEGER);
        $db->setQuery($q2)->execute();

        return true;
    }

    /** Produkt via REF/Title in Case übernehmen (Fallback) */
    public function addCaseItemFromCatalog(int $caseId, ?string $ref, ?string $title): int
    {
        $db = $this->getDbo();
        $pt = $this->productTable();

        $q = $db->getQuery(true)
            ->select(['id','ref','title'])
            ->from($db->quoteName($pt));

        if ($ref)   { $q->where($db->quoteName('ref')   . ' = ' . $db->quote($ref)); }
        if ($title) { $q->where($db->quoteName('title') . ' = ' . $db->quote($title)); }

        $db->setQuery($q);
        $row = $db->loadAssoc();
        if (!$row) {
            throw new \RuntimeException('Produkt nicht gefunden');
        }

        return $this->insertCaseItem($caseId, (int)$row['id'], (string)$row['ref'], (string)$row['title']);
    }

    /** Produkt per ID in Case übernehmen (Dropdown) */
    public function addCaseItemByProductId(int $caseId, int $productId): int
    {
        $db  = $this->getDbo();
        $pt  = $this->productTable();
        $pid = (int) $productId;

        $q = $db->getQuery(true)
            ->select(['id','ref','title'])
            ->from($db->quoteName($pt))
            ->where($db->quoteName('id') . ' = :pid')
            ->bind(':pid', $pid, ParameterType::INTEGER);

        $db->setQuery($q);
        $row = $db->loadAssoc();
        if (!$row) {
            throw new \RuntimeException('Produkt nicht gefunden');
        }

        return $this->insertCaseItem($caseId, (int)$row['id'], (string)$row['ref'], (string)$row['title']);
    }

    /** Gemeinsamer Inserter für Case-Item */
    private function insertCaseItem(int $caseId, int $productId, string $ref, string $title): int
    {
        $db  = $this->getDbo();

        $ins = $db->getQuery(true)
            ->insert($db->quoteName('#__fisc_project_case_item'))
            ->columns([
                $db->quoteName('case_id'),
                $db->quoteName('product_id'),
                $db->quoteName('product_ref'),
                $db->quoteName('product_title'),
                $db->quoteName('min_price'),
                $db->quoteName('mean_price'),
                $db->quoteName('max_price'),
                $db->quoteName('cogs_lc'),
            ])
            ->values(implode(',', [
                (int)$caseId,
                (int)$productId,
                $db->quote($ref),
                $db->quote($title),
                'NULL','NULL','NULL','NULL',
            ]));

        $db->setQuery($ins)->execute();
        return (int) $db->insertid();
    }

    /** Produktsuche fürs Dropdown (statisch) */
    public function getProductOptions(int $limit = 200): array
    {
        $db = $this->getDbo();
        $pt = $this->productTable();

        $q  = $db->getQuery(true)
            ->select(['id','ref','title'])
            ->from($db->quoteName($pt))
            ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($q, 0, $limit);
        return $db->loadObjectList() ?: [];
    }

    /**
     * Aggregatwerte (Min / Median / Max / COGS) für EIN Produkt – für AJAX addItem().
     * Median wird als mean_price-Alias geliefert (Kompatibilität zum Frontend).
     */
    public function getAggregatesForProduct(int $productId): array
    {
        $db  = $this->getDbo();
        $pid = (int) $productId;
        $pt  = $this->productTable();

        $sql = "
WITH sales AS (
  SELECT
    sd.sales_lc,
    /* COGS nur gültig, wenn qty = 1 und cogs_lc > 0 */
    CASE WHEN sd.qty = 1 AND sd.cogs_lc > 0 THEN sd.cogs_lc END AS cogs_lc
  FROM {$pt} pi
  LEFT JOIN #__fisc_sales_data sd
         ON sd.product_code = pi.ref
  WHERE pi.id = {$pid}
    AND sd.sales_lc IS NOT NULL
    AND sd.sales_lc >= 0
),
ordered AS (
  SELECT
    s.*,
    ROW_NUMBER() OVER (ORDER BY s.sales_lc) AS rn,
    COUNT(*)    OVER ()                     AS cnt
  FROM sales s
)
SELECT
  COALESCE(MIN(sales_lc),  0) AS min_price,
  COALESCE(MAX(sales_lc),  0) AS max_price,
  COALESCE(AVG(CASE
           WHEN cnt % 2 = 1 AND rn = (cnt + 1) / 2             THEN sales_lc
           WHEN cnt % 2 = 0 AND (rn = cnt/2 OR rn = cnt/2 + 1) THEN sales_lc
         END), 0) AS mean_price,
  COALESCE(MAX(cogs_lc),   0) AS cogs_lc
FROM ordered
";
        $db->setQuery($sql);
        $row = $db->loadAssoc() ?: [];

        return [
            'min_price'  => isset($row['min_price'])  ? (float)$row['min_price']  : 0.0,
            'max_price'  => isset($row['max_price'])  ? (float)$row['max_price']  : 0.0,
            'mean_price' => isset($row['mean_price']) ? (float)$row['mean_price'] : 0.0, // Median
            'cogs_lc'    => isset($row['cogs_lc'])    ? (float)$row['cogs_lc']    : 0.0,
        ];
    }

    /** Freitextpreis speichern (Autosave) */
    public function updateFreePrice(int $itemId, string $field, float $value): bool
    {
        $allowed = ['free_price1', 'free_price2', 'free_price3'];
        if (!\in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException('Unzulässiges Freipreisfeld');
        }

        $db  = $this->getDbo();
        $iid = (int) $itemId;

        $q  = $db->getQuery(true)
            ->update($db->quoteName('#__fisc_project_case_item'))
            ->set($db->quoteName($field) . ' = ' . $db->quote($value))
            ->where($db->quoteName('id') . ' = :iid')
            ->bind(':iid', $iid, ParameterType::INTEGER);

        $db->setQuery($q)->execute();
        return true;
    }

    /** Rohe Items eines Cases (ohne Aggregation) – optional verwendbar */
    public function getCaseItems(int $caseId): array
    {
        $db  = $this->getDbo();
        $cid = (int) $caseId;

        $q = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__fisc_project_case_item'))
            ->where($db->quoteName('case_id') . ' = :cid')
            ->bind(':cid', $cid, ParameterType::INTEGER)
            ->order($db->quoteName('id') . ' ASC');

        $db->setQuery($q);
        return $db->loadAssocList() ?: [];
    }

    public function getProject(int $projectId)
    {
        $db  = $this->getDbo();
        $pid = (int) $projectId;

        $q = $db->getQuery(true)
            ->select([
                'p.id',
                'COALESCE(CONCAT("Projekt #", ' . $pid . ')) AS name',
                'COALESCE(p.description, "") AS description',
                'f.name AS customer_name',
                'p.customer_id AS facility_id',
            ])
            ->from($db->quoteName('#__fisc_calculator_project', 'p'))
            ->leftJoin($db->quoteName('#__fisc_customer_facility', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('p.customer_id'))
            ->where($db->quoteName('p.id') . ' = :pid')
            ->bind(':pid', $pid, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($q);
        $row = $db->loadObject();

        if (!$row) {
            $row = (object) [
                'id'            => $pid,
                'name'          => 'Projekt #' . $pid,
                'customer_name' => '',
                'description'   => '',
                'facility_id'   => null,
            ];
        }

        return $row;
    }
}
