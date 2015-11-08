<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>JournalTouch Settings</title>
        <link rel="stylesheet" href="../css/foundation.min.css" />
        <link rel="stylesheet" href="../css/foundation-icons/foundation-icons.css" />
        <script src="../js/vendor/jquery.js"></script>
        <script src="../js/foundation.min.js"></script>
    </head>
<body>
    <?php include('menu.inc') ?>
    <h2>Update Journal Infos</h2>

<?php
require(__DIR__.'/../config.php');
$btn_cache  = (isset($_GET['clr_cache'])) ? true : false;
$btn_upd    = (isset($_GET['upd'])) ? true : false;
$optMeta    = (isset($_GET['optMeta'])) ? true : false;
$optRecent  = (isset($_GET['optRecent'])) ? true : false;
$optTags    = (isset($_GET['optTags'])) ? true : false;
$optCovers  = (isset($_GET['optCovers'])) ? true : false;

if ($btn_upd) {
  require_once($cfg->sys->basepath.'admin/services/class.UpdateInputCsv.php');
  $getInfos = new GetJournalInfos($cfg);
  $getInfos->update_journals_csv($optMeta, $optRecent, $optTags, $optCovers);
}

$del_message = '';
if ($btn_cache) {
  $files = glob($cfg->sys->data_cache.'*.cache*'); // get all file names by pattern
  $i = 0;
  foreach($files as $file) {
    if(is_file($file)) {
      unlink($file);
      $i++;
    }
  }
  $del_message = "<strong>Sucess: $i files deleted</strong>";
}

?>

<div style="border: thin solid #000000; width:400px; padding:10px;">
  <form method="get" action="index.php">
    <fieldset>
      <!-- Quick & Dirty - remove cached files -->
      <legend><b>Clear Cache</b></legend>
      If you enabled the caching in the config, you can clear it here:<hr />
      <button type="submit" name="clr_cache" value="true">Delete Cache</button> <?php echo $del_message; ?>
    </fieldset>
    <br />
    <fieldset>
      <!-- Quick & Dirty - Update input.csv -->
      <legend><b>Update options</b></legend>
      This updates your journals.csv with the dates for each journal issue. If you chose to fetch metadata, also some infos about publishers, legal infos and tags are fetched, mostly as tags for the tagcloud in JournalTouch. Be aware: metadata is only fetched the very first time. If you really want to redo it, you have to delete everything in column 14 ("JToc", "Jseek", "CRtoc").<hr />
      <label for="optMeta"><input type="checkbox" name="optMeta" checked="checked"> Fetch metadata<br>
      <label for="optRecent"><input type="checkbox" name="optRecent" checked="checked"> Fetch recent issues<br>
      <label for="optCovers"><input type="checkbox" name="optCovers" checked="checked"> Download/update covers<br>
      <label for="optTags"><input type="checkbox" name="optTags"> Clean tags (experimental)<sup>1</sup><br>
      <button name="upd" value="true" type="submit">Start</button>
      <br /><br />
      <sup>1</sup>You have to edit data/journals/tag-remap.txt. Format: "oldTag;newTag"
    </fieldset>
    <br />
    <fieldset>
      <!-- Quick & Dirty - Update input.csv -->
      <legend><b>JournalTOC Premium Update</b></legend>
      This updates your updates.json.txt with the dates for recent issue.<hr />
      <a href="services/getLatestJournals.php">Update Premium</a>
    </fieldset>
  </form>
</div>

<body>
</html>
