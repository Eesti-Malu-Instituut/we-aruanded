<html lang="et">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Aruanded · Eesti Kommunismiohvrid 1940–1991</title>
    <link
      href="https://cloud.typography.com/6935656/6118392/css/fonts.css"
      rel="stylesheet"
      type="text/css"
    />
    <link href="styles.css" rel="stylesheet" />
  </head>
  <?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  $env = parse_ini_file('.env');
  $servername = $env['DB_SERVERNAME'];
  $username = $env['DB_USERNAME'];
  $password = $env['DB_PASSWORD'];
  $db = $env['DB_NAME'];
  $jsonFilters = $env['JSON_FILTERS'];
  $jsonTableRows = $env['JSON_TABLEROWS'];
  require_once ('SSHMysql.php');

  $sshMySQL = new SSHMysql();

  $jsonFilters = file_get_contents($jsonFilters);
  $filtersArray = json_decode($jsonFilters, true);
  $SQL = $filtersArray['sql'];
  $filters = $filtersArray['filters'];
  $jsonTableRows = file_get_contents($jsonTableRows);
  $tableRows = json_decode($jsonTableRows, true);
  $subelements = $definedColumns = [];
  $currentYear = 1941;
  $definedColumns['default'] = $filtersArray['columns'];
  $columns = $definedColumns;
  foreach ($filters as $group) {
      foreach ($group as $id => $filter) {
          if (isset($filter['occurrence']) && $filter['occurrence'] > $currentYear) {
              $currentYear = $filter['occurrence']; // age is calculated by latest occurrence
          }
          $definedColumns[$id] = $group['data']['columns'];
          $subelements[$id] = $filter;
      }
  }

  $conn = new mysqli($servername, $username, $password, $db);
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  $filterParams = ['select', 'join', 'where'];
  foreach ($_GET as $parameter => $value) {
      if (array_key_exists($parameter, $subelements)) {
          if ($value) {
              $columns[] = $definedColumns[$parameter];
          }
          $filterSQL = $subelements[$parameter]['sql'];
          foreach ($filterParams as $filterParam) {
              if (array_key_exists($filterParam, $filterSQL) && $value !== '') {
                  if (is_array($filterSQL[$filterParam])) {
                      foreach ($filterSQL[$filterParam] as $key => $filter) {
                          $filter = str_replace('{{value}}', $value, $filter);
                          $SQL[$filterParam][$key][] = $filter;
                      }
                  } else {
                      $filterSQL[$filterParam] = str_replace('{{value}}', $value, $filterSQL[$filterParam]);
                      $SQL[$filterParam][] = $filterSQL[$filterParam];
                  }
              }
          }
          if (array_key_exists('extra_sql', $subelements[$parameter]) && $subelements[$parameter] !== '') {
              $SQL['extra_sql'] = $subelements[$parameter]['extra_sql'];
          }
      }
  }

  $columnCount = 0;
  foreach ($columns as $column) {
      if (count($column) > $columnCount) {
          $shownColumns = $column;
          $columnCount = count($column);
      }
  }
  foreach (['repressions', 'arrested', 'fled', 'military', 'gender'] as $subcriteria) {
      if (isset($SQL['where'][$subcriteria])) {
          $SQL['where'][$subcriteria] = '(' . implode(' OR ' , $SQL['where'][$subcriteria]) . ')';
      }
  }
  $select = implode(', ' , $SQL['select']);
  $from = implode(', ' , $SQL['from']);
  $join = (isset($SQL['join'])) ? implode(' ' , $SQL['join']) : '';
  $where = (isset($SQL['where']) && count($SQL['where'])) ? 'WHERE ' . implode(' AND ' , $SQL['where']) : '';
  $group_by = implode(', ' , $SQL['group_by']);
  $order_by = implode(', ' , $SQL['order_by']);
  $sql = "SELECT $select FROM $from $join $where GROUP BY $group_by ORDER BY $order_by ASC";

  $result = $sshMySQL->query($sql);
  $resultArray = [];
  $sshError = null;
  if (!isset($result['errorset'])) {
      $resultArray = $result['dataset'];
  } else {
      $sshError = $resultArray['errorset'];
  }

  $ages = $tableRows;
  $total['repressed'] = $totalFaulty['repressed'] = 0;
  $total['arrested'] = $totalFaulty['arrested'] = 0;
  $total['deported'] = $totalFaulty['deported'] = 0;
  foreach ($resultArray as $item) {
      $age = $currentYear - (int) $item->year;
      $floor = roundDownToFive($age);
       if (array_key_exists((string) $floor, $ages)) {
           $ages[$floor]['repressed'] += $item->records;
     } else {
          $totalFaulty['repressed'] += $item->records;
      }
      $total['repressed'] += $item->records;
  }
  if (isset($SQL['extra_sql']) && count($columns) === 2) {
      foreach ($SQL['extra_sql'] as $key => $extraSQL) {
          $extraResult = $sshMySQL->query($extraSQL);
          $extraResult = $extraResult['dataset'];
          foreach ($extraResult as $item) {
              $age = $currentYear - (int) $item->year;
              $floor = roundDownToFive($age);
              if (array_key_exists((string) $floor, $ages)) {
                  $ages[$floor][$key] += $item->records;
              } else {
                  $totalFaulty[$key] += $item->records;
              }
              $total[$key] += $item->records;
          }
      }
  }

  function roundDownToFive($n,$x=5) {
      return floor( $n / $x ) * $x;
  }
  ?>
  <body style="/*user-select: none*/">
    <div class="col-12">
      <div class="reports-content-wrapper">
        <div class="reports-wrapper">
          <div class="reports-totals reports-totals-by-age-group">
            <h5 class="repressed-total-by-age-group">Represseeritute/põgenike üldarv vanusegrupi järgi</h5>
              <?php
              if ($sshError) {
                  ?>
                  <h5 class="repressed-total-by-age-group"><?=$sshError?></h5>
                  <?php
              }
              ?>
          </div>
          <div class="reports-table-wrapper">
            <table class="reports-table">
              <thead class="reports-table-head">
                <tr class="reports-table-row">
                  <th class="reports-table-age-group">Vanusegrupp</th>
                    <?php
                    foreach ($shownColumns as $columnId => $columnText) {
                        ?>
                        <th class="reports-table-number-of-<?=$columnId?>"><?=$columnText?></th>
                        <?php
                    }
                    ?>
                </tr>
              </thead>
              <tbody class="reports-table-body">
              <?php
              foreach ($ages as $age => $value) {
              ?>
                <tr class="reports-table-row">
                  <td><?=$value['text']?></td>
                    <?php
                    foreach ($shownColumns as $columnId => $columnText) {
                        ?>
                        <td class="reports-table-data"><?=$value[$columnId];?></td>
                        <?php
                    }
                    ?>
                </tr>
              <?php
              }
              ?>
              <tr class="reports-table-row">
                  <td>Andmed puuduvad:</td>
                  <?php
                  foreach ($shownColumns as $columnId => $columnText) {
                      ?>
                      <td class="reports-table-total-number-of-<?=$columnId?> reports-table-data"><?=$totalFaulty[$columnId];?></td>
                      <?php
                  }
                  ?>
              </tr>
              <tr class="reports-table-row">
                  <td class="reports-table-total-text">Kokku:</td>
                  <?php
                  foreach ($shownColumns as $columnId => $columnText) {
                      ?>
                      <td class="reports-table-total-number-<?=$columnId?> reports-table-data"><?=$total[$columnId];?></td>
                      <?php
                  }
                  ?>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="filters-wrapper">
          <form
            class="reports-filters-form"
            action="index.php"
            method="GET"
          >
            <h5>Filtrid:</h5>
            <div class="report-filter-inputs-wrapper">
                <?php
                foreach ($filters as $groupId => $group) {
                    ?>
                <div id="<?=$groupId?>">
                    <h6><?=$group['data']['header']?></h6>
                    <?php
                    foreach ($group as $id => $filter) {
                        if ($id === 'data' || (isset($filter['visible']) && $filter['visible'] === 'false')) {
                            continue;
                        }
                        $label = (isset($filter['label'])) ? $filter['label'] : '';
                        $placeholder = (isset($filter['placeholder'])) ? 'placeholder="' . $filter['placeholder'] . '"' : '';
                        switch ($filter['type']) {
                            case 'checkbox':
                                $class = 'report-filter-checkbox';
                                $type = 'checkbox';
                                $wrapperDiv = '<div class="report-filter-checkbox-wrapper">';
                                $wrapperEnd = '</div>';
                                $value = (isset($_GET[$id]) && $_GET[$id] === 'on') ? 'checked="checked"' : '';
                                break;
                            case 'input':
                                $class = 'report-filter-text-input';
                                $type = 'search';
                                $wrapperDiv = $wrapperEnd = '';
                                $value = (isset($_GET[$id]) && $_GET[$id] !== '') ? 'value="' . $_GET[$id] . '"' : '';
                        }
                        ?>
                        <?=$wrapperDiv?>
                        <input
                                class="<?=$class?>"
                                type="<?=$type?>"
                                <?=(isset($filter['disabled']) && $filter['disabled'] === 'true') ? 'disabled' : '';?>
                                name="<?=$id?>"
                                <?=$placeholder?>
                                <?=$value?>
                        />
                        <label for="<?=$id?>"><?=$label?></label>
                        <?=$wrapperEnd?>
                        <?php
                    }
                    ?>
                </div>
                    <?php
                }
                ?>

                <button class="report-filter-submit-button" type="submit">
                Filtreeri
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
