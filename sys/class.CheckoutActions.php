<?php
/**
 * Checkout Actions
 *
 * Actions for the checkout
 *
 * Time-stamp: "2014-04-10 15:03:40 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
class CheckoutActions
{

  protected $mail;

  protected $html;
  protected $endnote;
  public $contents;


  public function __construct($cfg)
    /* load some configuration */
  {
    $this->sys          = $cfg->sys;
    $this->mail         = $cfg->mail;
    $this->prefs        = $cfg->prefs;
    $this->translations = $cfg->translations;
  }

  function getArticlesAsHTML($file) {
    /* this is most likely to break, please rewrite (works only because $i++ is in the last if-item in array order) */
    $this->html = ''; // clear if we have called it before (e.g. from mailer)
    $this->html.='<div class="panel">';

    if (($handle = fopen($file, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 0, "#")) !== FALSE) {
        $this->html.= "<div data-citeproc-json='".$data[2]."'>";
        $this->html.= '<h5><a href="'.$data[1].'" class="popup">'.$data[0].'</a></h5>';
        $this->html .='</div><hr/>';
      }
      fclose($handle);
    }
    $this->html.='<div id="citation"></div></div>';
    return $this->html;
  }

  function saveArticlesAsCSV($mylist) {
    /* fputcsv does not really work, because the simpleCart js array is one huge array ($i=count) */

    $i = 1;

    foreach ($mylist as $key => $value) {

      /* this is most likely to break, please rewrite (works only because $i++ is in the last if-item in array order) */

      if (strpos($key, 'item_name_'.$i) !== false) {
        $this->contents .= $value."#";
      }

      elseif (strpos($key, 'item_link_'.$i) !== false) {
        $this->contents .= $value . "#";

      }

      elseif (strpos($key, 'item_citestr_'.$i) !== false) {
        $this->contents .= $value . "#";

      }

      elseif (strpos($key, 'item_options_'.$i) !== false) {
        $this->contents .= $value;
        $this->contents .= "\r\n";
        $i++;
      }

    }


    /* save cart in a hashed filename */
    $length = 12;
    $timestamp = date("m.d.y h:i:s");
    $hash = substr(hash('md5', $timestamp), 0, $length); // Hash it
    $rand_no = rand(0,500); // add a random number to make sure we do not write to the same file (e.g. two page requests in the same second)

    global $file;
    $file = $this->sys->data_export.$hash."-".$rand_no.".csv";
    file_put_contents($file, $this->contents, LOCK_EX) or die("could not write to file!");

  }

  function sendArticlesAsMail($file, $email) {

    try {
      $message = (isset($_POST['message']) && !empty($_POST['message']) ? '<p>'.$this->translations['mail_bodyMessage'][$this->prefs->current_lang].': '.$_POST['message'].'</p>' : '');
      $email->CharSet = 'utf-8';
      $email->Encoding = '8bit';
      $email->isHTML(true);
      $file = isset($_POST['file']) ? $_POST['file'] : '';
      $fileBody = (!empty($file)) ? $this->getArticlesAsHTML($file) : '';

      // Is the domain restriction set?
      if ($this->mail->domain && isset($_POST['username'])) {
        $usermail = $_POST['username'].'@'.$this->mail->domain;
        $user     = $_POST['username'];
      }
      elseif (isset($_POST['username'])) {
        $usermail = $_POST['username'];
        $user     = $usermail;
      }
      else {
        $user = $this->mail->fromAddress;
      }

      if (isset($_POST['action']) && $_POST['action'] == "sendArticlesToLib") {
        $email->FromName  = $usermail;
        $email->From      = $usermail;
        $email->Subject   = $this->translations['mail_subjectToLib'][$this->prefs->current_lang] . ' ('.__('from').' '.$user.')';
        $email->Body     = '<h2>'.$this->translations['mail_bodyOrder'][$this->prefs->current_lang].'</h2><p>'.$message.'</p><hr/>'.$fileBody;
        $email->AddAddress($this->mail->toAddress);
      } else {
        $email->FromName  = $this->translations['mail_fromName'][$this->prefs->current_lang];
        $email->From      = $this->mail->fromAddress;
        $email->Subject   = $this->translations['mail_subjectToUser'][$this->prefs->current_lang];
        $email->Body     = '<h2>'.$this->translations['mail_bodySalutation'][$this->prefs->current_lang].'</h2>'.$message.'<hr/>'.$fileBody.'<p>'.$this->translations['mail_bodyClosing'][$this->prefs->current_lang].'</p>';
        $email->AddAddress($usermail);
      }



      /* add attachment (export only) TODO */
      if (isset($_POST['attachFile'])) {
        if ($_POST['attachFile'] == "endnote") {
          $this->saveArticlesAsEndnote($file);
          $file_to_attach = $file.".ris";
          $filename = "citations_endnote.ris";
        } else if ($_POST['attachFile'] == "csv") {
          $file_to_attach = $file;
          $filename = "citations.csv";
        }  else { /*default*/
          $file_to_attach = $file;
          $filename = "citations.csv";
        }
        $email->AddAttachment( $file_to_attach , $filename );
      }

      $email->Send();
      return "OK";
      /* error handling */
    } catch (phpmailerException $e) {
      return $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
      return 'Error: '. $e->getMessage(); //Boring error messages from anything else!
    }

  }

  function sendFeedback($email) {

    try {
      $message = (isset($_POST['message']) && !empty($_POST['message']) ? $_POST['message'] : '');
      $email->CharSet = 'utf-8';
      $email->Encoding = '8bit';
      $email->isHTML(false);

      $email->Body     = $message;
      // Is the domain restriction set?
      if ($this->mail->domain && isset($_POST['username'])) {
        $usermail = $_POST['username'].'@'.$this->mail->domain;
        $user     = $_POST['username'];
      }
      elseif (isset($_POST['username'])) {
        $usermail = $_POST['username'];
        $user     = $usermail;
      }
      else {
        $user = $this->mail->fromAddress;
      }

      $email->Subject   = __('JournalTouch Feedback') . ' ('.__('from').' '.$user.')';
      $email->From      = $user;
      $email->FromName  = $user;
      $email->AddAddress($this->mail->toAddress);

      $email->Send();
      return "OK";
      /* error handling */
    } catch (phpmailerException $e) {
      return $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
      return $e->getMessage(); //Boring error messages from anything else!
    }

  }

  function saveArticlesAsEndnote($file) {

    $this->endnote = ''; // clear if we have called it before (e.g. from mailer)

    if (($handle = fopen($file, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 0, "#")) !== FALSE) {

        /* strip our csv again - ugly-ugly but the alternative is adding functions to simpleCart.js (only limited fields available) */
        /* BEWARE! This will not match all data (too heterogeneous), but at least some of it */
        preg_match('/[^:]*/',$data[0],$au); /* author */
preg_match('/^.*?:\s(.*)/',$data[0],$ti); /* title */
preg_match('/source:\s([^,]*)/',$data[2],$jo); /* journal title */
preg_match('/Vol.?\s(\d+)/',$data[2],$vo); /* volume */
preg_match('/(\d+)\s-\s(\d+)/',$data[2],$pp); /* pages (start & end, grouping) */
$this->endnote.= 'TY  - JOUR'.PHP_EOL;
if (isset($au[0])) $this->endnote.= 'AU  - '.$au[0].PHP_EOL;
if (isset($ti[1])) $this->endnote.= 'TI  - '.$ti[1].PHP_EOL;
if (isset($jo[1])) $this->endnote.= 'JO  - '.$jo[1].PHP_EOL;
if (isset($vo[1])) $this->endnote.= 'VL  - '.$vo[1].PHP_EOL;
if (isset($pp[1])) $this->endnote.= 'SP  - '.$pp[1].PHP_EOL;
if (isset($pp[2])) $this->endnote.= 'EP  - '.$pp[2].PHP_EOL;
if (isset($data[1])) $this->endnote.= 'UR  - '.$data[1].PHP_EOL;
$this->endnote.= 'ER  - '.PHP_EOL; /* end of record */
      }
      fclose($handle);
    }

    $fileEndnote = $file.".ris";

    file_put_contents($fileEndnote, $this->endnote, LOCK_EX) or die("could not write Endnote to file!");

  }

}
?>
