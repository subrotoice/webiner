<?php
require_once(dirname(__DIR__) . "/conexion.php");
require_once(dirname(__DIR__) . "/webinar-reg/functions.php");
if (!isset($_SESSION)) {
  session_start();
}



require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "helper.php");

$webinar_id  = filter_var($_GET["webinar_id"], FILTER_SANITIZE_NUMBER_INT);

$webinar_admin_id  = filter_var($_GET["admin_id"], FILTER_SANITIZE_NUMBER_INT);

$fetch_user_webinar   =  $pdo_db->row("SELECT webinar_admin_id FROM webinar WHERE ID = :webinar_id ", array("webinar_id" => $_GET['webinar_id']));

if ($fetch_user_webinar['webinar_admin_id'] != $webinar_admin_id) {
  $moderator_id = getAdminModerator($webinar_admin_id);
  if ($moderator_id > 0) {
    $webinar_admin_id = $moderator_id; //If Moderator Present then go to moderator webinar Not Admin's
  }
} else {
  $webinar_admin_id  = filter_var($_GET["admin_id"], FILTER_SANITIZE_NUMBER_INT);
}

$webinar_token  = filter_var($_GET["token"], FILTER_SANITIZE_STRING);

$webinar_user_relation = getWebinarUserData($webinar_token, "relation_id")["relation_id"];

if ($webinar_user_relation) {

  $webinar_user_table = $pdo_db->row("SELECT user_id,webinar_id FROM webinar_user_xref WHERE  id = :relation_id", array("relation_id" => $webinar_user_relation));
}

if ($webinar_user_table["user_id"]) {
  $webinar_user_name = $pdo_db->row(
    "SELECT user_name FROM webinar_users WHERE  ID = :user_id",
    array("user_id" => $webinar_user_table["user_id"])
  );
}

// Set Username for Webinar
if ($webinar_user_name) {
  $webinar_user_name =  htmlspecialchars($webinar_user_name["user_name"]);
}

// Set Webinar's Id
if ($webinar_user_table["webinar_id"]) {
  $webinar_id = intval($webinar_user_table["webinar_id"]);
}

if (!$webinar_user_relation) {
  $redirect_url  = getRegisterWeibnarUrl($webinar_id, filter_var($_GET["admin_id"], FILTER_SANITIZE_NUMBER_INT));
  header("Location: $redirect_url");
  die();
}

$webinar_data  = getWebinar($webinar_id, ["name, description, webinar_admin_id, presentor, presentor_image, topic,notify_email, banner_image, banner_color, bg_image, footer_image, primary_color, secondary_color"], $webinar_admin_id);

// var_dump($webinar_data);
// exit;
// $webinar_name = htmlspecialchars(getWebinar($webinar_id, "name", $webinar_admin_id)["name"]);
$webinar_name = $webinar_data["name"];

$fetch_user   =  $pdo_db->row("SELECT email,pagina_corporativa FROM usuario WHERE clave = :admin_id ", array("admin_id" => $_GET['admin_id']));
$webinar_admin_email = $fetch_user['email'];

// No Webinar With this ID existsem
if (!$webinar_name) {
  echo "No Such Webinar";
  return false;
}

// check Webinar Type
$webinar_type  = getWebinar($webinar_id, "type", $webinar_admin_id)["type"];

// check hangout status
$hangout_video_status = hangoutVideoStatus($webinar_id);

if ($webinar_type == "automated") {

  if (isset($_SESSION["webinar_late_video"])) {

    unset($_SESSION["webinar_late_video"]);
    //Show Late Video
    $video_url = trim(htmlspecialchars(getWebinar($webinar_id, "video_url_late", $webinar_admin_id)["video_url_late"]));
  } else {
    $video_url = trim(htmlspecialchars(getWebinar($webinar_id, "video_url", $webinar_admin_id)["video_url"]));
  }


  $ext = pathinfo($video_url, PATHINFO_EXTENSION);
  $posterUrl = str_replace($ext, "jpg", $video_url);

  $org_video_url = $video_url;

  $video_url = str_replace("." . $ext, "", $video_url);
  $video_name = basename($video_url);
} else if ($webinar_type == "google_hangout") {
  $video_url  = htmlspecialchars(getWebinar($webinar_id, "hangout_link", $webinar_admin_id)["hangout_link"]) . "?autoplay=1&showinfo=0&controls=0";

  $org_video_url = htmlspecialchars(getWebinar($webinar_id, "hangout_link", $webinar_admin_id)["hangout_link"]) . "?autoplay=1&showinfo=0&controls=0";
}

$webinar_attendees = getWebinarActiveAttendees($webinar_id);

$no_of_attendees = 0;

if ($webinar_attendees) {
  $no_of_attendees = count($webinar_attendees);
}

// generate a new cookie For the Enquiry form
$newToken = $webinar_token;
$_COOKIE["enquiry_form_cookie"] = $newToken;
setcookie("enquiry_form_cookie", $newToken, time() + 8600,  "/");


// generate a new token for the $_SESSION superglobal and put them in a hidden field
//$ajaxTokenWebinarPurchase = generateFormToken("get_webinar_purchase_link_status");

/***** Initialize User Chat Session *****/


// If Token Expired OR not coming from redirect page Fetch from URL
if (!isset($_SESSION['live_webinar_token'])) {
  $_SESSION['live_webinar_token'] = filter_var($_GET["token"], FILTER_SANITIZE_STRING);
}

if ($_SESSION['live_webinar_token'] && isset($_COOKIE)) {
  $_REQUEST["login"] = 1;
  $_REQUEST["user_login_to_chat"] = 1;
  $_COOKIE["live_webinar_token_user"] = $_SESSION['live_webinar_token'] . "_" . $webinar_id . "_" . $_GET["admin_id"];
  setcookie("live_webinar_token_user", $_SESSION['live_webinar_token'] . "_" . $webinar_id . "_" . $_GET["admin_id"]);
}

$getWebinarInfo = getWebinarById($webinar_id);

define("CHAT_STATUS", 1);
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "chat" . DIRECTORY_SEPARATOR . "chat_vionika_essentials.php");

if (!isset($chat_online) || !$chat_online) {
?>
<?php
  $chat_error_msg =  "<div class='red-text' style='padding-left:210px; color:#000066;'>ONLINE CHAT (APAGADO/ OFF)</div>";
}
function get_chat()
{
  return include("chat/index.php");
}

$dummy_user_qu = $pdo_db->query("SELECT name,admin_id from dummy_users WHERE admin_id = :admin_id", array("admin_id" => $webinar_admin_id));
// ==============================================
// Get Facebook pixel code and store in variable.
// @see /usuarios/functions.php / getUserPixelCodes()
// Boris Trivic 2017-01-10
include_once "../usuarios/functions.php";

$webinar_fb_pixel_code = getUserPixelCode($webinar_admin_id, "fbpixel_wasistencia");
// var_dump($webinar_fb_pixel_code);
?>
<!DOCTYPE HTML>
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VIONIKO</title>
  <meta name="description" content="" />
  <meta name="keywords" content="" />
  <!---->
  <link rel="icon" href="favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" type="text/css" href="../assets/live-webnar/assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link href="css/poll.css" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="css/redesign.css" type="text/css" />
  <script src="../js/jquery.min.js"></script>
  <script src="../js/jquery-form-plugin.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/1.4.5/socket.io.min.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <?= $webinar_fb_pixel_code ?>

  <?php if ($webinar_type != "google_hangout") : ?>
    <script>
      jQuery(document).ready(function() {
        loading("loader");
      });
      jQuery(window).load(function() {
        var myVideo = document.getElementsByTagName('video')[0];
        myVideo.addEventListener('canplaythrough', showAccessBtn(), false);
      });
    </script>
  <?php endif; ?>
  <script>
    var frontend_online_list = 'frontend_online_list';
    var ChatEnableWithoutRefresh = {};
    ChatEnableWithoutRefresh.value = 0;
    var webinar_id = '<?php echo $webinar_id; ?>';
  </script>
  <script>
    var chat_html = '';

    function startPollingForChat() {
      var checkStatus = setInterval(function() {
        checkChatStatus(checkStatus)
      }, 8000);
    }

    var globalPageOpenTime = new Date();


    // Show purchase button if configured time has passed since the webinar opening.
    var globalIntervalPurchaseBtn;

    function delayedShowPurchaseButton() {

      var configuredDelayMinutes = <?= intval($getWebinarInfo['purchase_button_time']) ?>;
      // var configuredDelayDate = new Date(0,0,0,0, configuredDelayMinutes, 0,0);

      globalIntervalPurchaseBtn = setInterval(function() {


        if (video.currentTime < 1) {
          return null;
        }

        // console.log("Test...");

        var currentVideoTime = window.sessionStorage.getItem("playBackVideoTime") ? window.sessionStorage.getItem("playBackVideoTime") : 0;
        currentVideoTime = Math.round(currentVideoTime);

        // console.log("currentVideoTime: " + currentVideoTime);


        // Generate time when to show the purchase button
        // console.log("decreasing showTIme for " + ((currentVideoTime * 1000)) + " seconds");
        var showDateTime = new Date(globalPageOpenTime.getTime() - (currentVideoTime * 1000) + configuredDelayMinutes * 60 * 1000);

        var now = new Date();
        // console.log("conf: " + configuredDelayMinutes );
        // console.log("conf: " + configuredDelayMinutes 
        //       + ", now: " + now 
        //       + ", opened: " + globalPageOpenTime 
        //       + ", showButtonDateTime: " + showDateTime);

        if (showDateTime < now) {
          jQuery('.webinar_purchase').html('<a href="' + '<?= $fetch_user['pagina_corporativa'] ?>' + '" target="_blank" class="link_report btn btn-danger" onclick="link_report(event);"><?= WEBINAR_PURCHASE_BUTTON_TEXT ?></a>');
          clearInterval(globalIntervalPurchaseBtn);
        }

      }, 10 * 1000)
    }



    // Check If Chat Enabled By Admin
    function checkChatStatus(checkStatus) {
      var chatHtml = jQuery("#chat-section #content").length;

      jQuery.ajax({
          type: "GET",
          url: "chat/chat_vionika_essentials.php",
          data: {
            token: "<?php echo $_COOKIE['enquiry_form_cookie'] ?>",
            chatHtml: chatHtml,
            ajaxRequest: 1,
            webinarId: <?php echo $webinar_id ?>,
            webinarAdminId: <?php echo filter_var($_GET["admin_id"], FILTER_SANITIZE_NUMBER_INT); ?>
          }
        })
        .done(function(chatOnline) {
          if (!chatOnline) {
            return false;
          }

          var obj = JSON.parse(chatOnline);

          // if( obj && obj.purchase_button == 1) {
          //     jQuery('.webinar_purchase').html('');
          // } else if(obj.purchase_button != null && obj.purchase_button) {
          //   jQuery('.webinar_purchase').html('<a href="' + obj.purchase_button +'" title="'+ obj.purchase_button +'" target="_blank" class="link_report btn btn-danger" onclick="link_report(event);"><?php echo WEBINAR_PURCHASE_BUTTON_TEXT; ?></a>');
          // }



          if (obj && obj.hangout_status == 1) {
            jQuery('#player_holder').html(' <input type="hidden" id="video_status" value="message"><p style="line-height: 40px;width: 100%; font-size: 32px;padding-top: 100px; padding-bottom: 100px;">AGRADECEMOS SU ASISTENCIA / THANK YOU FOR COMING</p>');
          } else if (obj.hangout_status == 0) {
            if (jQuery('#video_status').val() == 'message') {
              var hangout_video_html = ' <input type="hidden" id="video_status" value="video"><iframe id="hangout_video" style="position: relative; height: 50vh; width: 100%;" scrolling="no" frameborder="0" src="<?php echo $org_video_url ?>" frameborder="0" allowfullscreen></iframe>';
              jQuery('#player_holder').html(hangout_video_html);
            }
          }

          if (chatOnline == "authentication_failed" || (typeof ajaxChat != "undefined" && ajaxChat.socketError)) {
            // IF Socket Connection Error then disable chat but continue to poll for other varaibles
            if (chatOnline == "authentication_failed") {
              clearInterval(checkStatus);
            }

            jQuery("#chat-section").empty();
            jQuery("#chat-section").append("<div class='red-text' id='chat-banned-msg'>ONLINE CHAT (APAGADO/ OFF)</div>");
            return false;
          }

          if (obj.chat_status == "do_nothing") {
            return;
          }

          if (obj.chat_status == 'online') {
            ChatEnableWithoutRefresh.value = 1;
            if (!chatHtml) {
              jQuery.ajax({
                  type: "GET",
                  url: "chat/index.php",
                  data: {
                    login: 1,
                    user_login_to_chat: 1,
                    webinar_id: <?php echo $webinar_id ?>
                  },
                })
                .done(function(data) {
                  chat_html = data;
                  jQuery("#chat-section").html(chat_html);
                });
              //jQuery("#chat-banned-msg").append("ONLINE CHAT (APAGADO/ OFF) ");
            }
          } else {
            if (chatHtml) {
              jQuery("#chat-section").empty();
              jQuery("#chat-section").append("<div class='red-text' id='chat-banned-msg'>ONLINE CHAT (APAGADO/ OFF)</div>");
            }
          }
        });
    }
  </script>
  <style>
    .rowOdd a {
      color: #000000;
    }

    .rowOdd ul {
      display: none;
    }

    .rowEven a {
      color: #000000;
    }

    .rowEven ul {
      display: none;
    }

    .right-box div#chat-section {
      border-radius: 1rem;
      padding-top: 1rem;

    }


    .video-box,
    .left-box,
    .main-wrapper h3 {
      border-radius: 1rem;
    }

    <?php
    // var_dump($webinar_data['footer_image']); exit;
    // if (!empty($webinar_data['banner_image']) && file_exists(__DIR__ . '/../' . $webinar_data['banner_image'])) {
    //   echo ".header {
    //     background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['banner_image']) . "') center no-repeat;
    //     background-size: cover;
    // }";
    // }
    $bannerColor = $webinar_data['banner_color'];
    if (!empty($webinar_data['banner_image']) && file_exists(__DIR__ . '/../' . $webinar_data['banner_image'])) {
      echo ".header {
        background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['banner_image']) . "') center center no-repeat " . $bannerColor . ";
        border-bottom: 5px solid;
        border-image: linear-gradient(to right, #E9E4F0, #D3CCE3) 1;
    }";
    } else if (!empty($webinar_data['banner_color'])) {
      echo ".header {
        background: " . $bannerColor . ";
        border-bottom: 5px solid;
        border-image: linear-gradient(to right, #E9E4F0, #D3CCE3) 1;
    }";
    }

    if (!empty($webinar_data['bg_image']) && file_exists(__DIR__ . '/../' . $webinar_data['bg_image'])) {
      echo ".world-bg {
        background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['bg_image']) . "') center no-repeat;
        background-size: cover;
        max-width: 100%;
        height: 100vh;
    }";
      echo ".container {
      background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['bg_image']) . "') center no-repeat;
      background-size: cover;
  }";
      echo "body {
      background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['bg_image']) . "') repeat-y center top;
      background-size: cover;
  }";
    }

    if (!empty($webinar_data['footer_image']) && file_exists(__DIR__ . '/../' . $webinar_data['footer_image'])) {
      echo ".footer {
        background: url('https://vioniko.com/" . htmlspecialchars($webinar_data['footer_image']) . "') center no-repeat;
        background-size: cover;
    }";
    }

    if (!empty($webinar_data['primary_color'])) { /* Red */
      echo ".main-wrapper h3 {
        background: " . $webinar_data['primary_color'] . ";
    }";

      echo ".combine {
        background: " . $webinar_data['primary_color'] . ";
    }";

      echo ".f-times {
        margin-top: 20px;
        padding-top: 20px;
    }";
      echo ".req-form, .req-form .form-title {
        background: " . $webinar_data['primary_color'] . " !important;
    }";
    }

    if (!empty($webinar_data['secondary_color'])) {
      echo ".left-box {
          background: " . $webinar_data['secondary_color'] . " !important;
      }";

      echo ".two-boxes .right-box {
          background: " . $webinar_data['secondary_color'] . " !important;
          margin-top: 0;
          height: 510px;
      }";

      echo ".f-form2 {
          padding-bottom: 25px;
      }";

      echo ".video-box {
          background: " . $webinar_data['secondary_color'] . " !important;
      }";

      echo ".right-box div#chat-section {
          background: " . $webinar_data['secondary_color'] . " !important;
      }";
    }
    ?>
    /* Desktop (side by side) */
    @media (max-width: 768px) {
      .two-boxes {
        display: flex;
        flex-wrap: wrap;
      }

      .two-boxes .col-sm-5,
      .two-boxes .col-sm-7 {
        flex: 1 1 100%;
        /* flex: 1; */
      }

      /* Ensure first column shows first on mobile */
      .two-boxes .col-sm-5 {
        order: 2;
        margin-top: 20px;
      }

      .two-boxes .col-sm-7 {
        order: 1;
      }

      .header {
        display: none;
      }

      .header-border {
        height: 5px;
        margin-top: 5px;
        margin-bottom: 23px;
      }

      .footer {
        position: relative;
      }
    }


    @media (max-width: 600px) {
      #overlay {
        display: flex;
        opacity: 1;
        background: #000000c2;
        filter: alpha(opacity=80);
        width: 100%;
        height: 100%;
        z-index: 999;
        top: 0;
        left: 0;
        position: fixed;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }

      #access-btn {
        height: 60px;
        position: unset;
        margin: 0px;
        font-weight: bolder;
        font-size: 12px;
        width: 100%;

      }
    }

    @media (max-width: 768px) {
      .right-box div#chat-section {
        position: unset;
        width: auto;
        background: #013058;

      }

      .right-box div#chat-section {
        position: static;
        width: 100%;
        background: #013058;
        height: auto;
        right: 0;
      }

      .pawan-row {
        display: flex;
        flex-direction: column;
        width: 100%;

      }

      .pawan-order-2 {
        order: 2;


      }

      .pawan-order-1 {
        order: 1;


      }

      .pawan-order-3 {
        order: 3;
      }



      .pawan-row {
        margin-right: -0px;
        margin-left: -0px;
      }
    }
  </style>
  <?php
  // Webinar Chat
  // $admin_id = $_GET['admin_id'];
  $admin_id = $webinar_admin_id;
  $chat = mysql_fetch_array(mysql_query("SELECT * FROM usuario2 WHERE usuario_id='$admin_id'", $conexion));
  $chat_code_webinar = $chat['chat_code_webinar'];
  echo base64_decode($chat_code_webinar);
  ?>
</head>



<body class="home">

  <!-- Ambient Cybernetic Glow Effects -->
  <div class="cyber-bg-overlay"></div>
  <div class="cyber-globe-bg"></div>
  <div class="cyber-light-streak-1"></div>
  <div class="cyber-light-streak-2"></div>
  <div class="cyber-light-streak-3"></div>

  <!-- Top Header Navigation -->
  <header class="header">
    <div class="header-container">
      <div class="brand-section">
        <div class="brand-logo-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="#00d2ff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="2" y1="12" x2="22" y2="12"></line>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
          </svg>
        </div>
        <span class="brand-name">VIONIKO</span>
        <span class="brand-divider"></span>
        <span class="brand-tagline">CONOCIMIENTO | ESTRATEGIA | RESULTADOS</span>
      </div>
      <div class="live-badge-section">
        <span class="live-indicator-dot"></span>
        <span class="live-text"><?= defined('WEBINAR_LIVE_TITLE') ? WEBINAR_LIVE_TITLE : 'WEBINAR EN VIVO' ?></span>
      </div>
    </div>
  </header>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper">
    <div class="container">
      
      <!-- Video Window Card -->
      <div class="video-window-card">
        <!-- macOS Window Header Bar -->
        <div class="window-titlebar">
          <div class="window-dots">
            <span class="window-dot dot-red"></span>
            <span class="window-dot dot-yellow"></span>
            <span class="window-dot dot-green"></span>
          </div>
          <div class="window-title-text">PowerPoint Slide Show - [Presentacion24x7Breve]</div>
          <div class="window-header-spacer"></div>
        </div>

        <!-- Video Player Stream Container -->
        <div class="video-stream-box">
          <div id="video_player">
            <div align="center" id="player_holder">
              <?php
              if ($webinar_type == "google_hangout"):
                if ($hangout_video_status == NULL) {
              ?>
                  <input type="hidden" id="video_status" value="message">
                  <p style="line-height: 40px;width: 100%; font-size: 32px;padding-top: 100px; padding-bottom: 100px; color:#ffffff;">EN BREVE COMENZAMOS / WE WILL START SOON</p>
                <?php } elseif ($hangout_video_status == 0) { ?>
                  <input type="hidden" id="video_status" value="video">

                  <iframe
                    id="hangout_video"
                    style="position: relative; height: 50vh; width: 100%;"
                    src="<?php echo $video_url . '?autoplay=1&mute=1&controls=1&rel=0'; ?>"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen>
                  </iframe>
                <?php } else { ?>
                  <input type="hidden" id="video_status" value="message">
                  <p style="line-height: 40px;width: 100%; font-size: 32px;padding-top: 100px; padding-bottom: 100px; color:#ffffff;">AGRADECEMOS SU ASISTENCIA / THANK YOU FOR COMING</p>
                <?php } ?>
              <?php elseif ($webinar_type == "automated"): ?>
                <video id="video" width="100%" preload="auto" poster="<?php echo $posterUrl ?>" playsinline webkit-playsinline>
                </video>
              <?php endif; ?>
              <div id="divreset"></div>
              <div id="divdestroy"></div>
            </div>
          </div>
        </div>

        <!-- Title Bar Below Video -->
        <div class="webinar-title-bar">
          <div class="title-line"></div>
          <span class="webinar-title-text"><?php echo $webinar_name; ?></span>
          <div class="title-line"></div>
        </div>

        <!-- Delayed Purchase Button Container -->
        <div align="center" class="webinar_purchase"></div>
      </div>

      <!-- Control Center Panel / Responsive Modules (Desktop 3-Columns / Mobile Accordion Cards) -->
      <div class="control-center-panel">
        <div class="control-modules-row">
          
          <!-- Module 1: Audio Configuration -->
          <?php if ($webinar_type != "google_hangout") : ?>
          <div class="control-module module-audio open" id="accordion-audio">
            <div class="accordion-header" onclick="toggleAccordion('accordion-audio')">
              <div class="accordion-header-left">
                <div class="module-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                  </svg>
                </div>
                <span class="module-title"><?php echo defined('AUDIO_CONFIGURATION') ? AUDIO_CONFIGURATION : 'CONFIGURACIÓN DE AUDIO'; ?></span>
              </div>
              <div class="accordion-chevron">
                <i class="fa fa-chevron-down"></i>
              </div>
            </div>
            <div class="accordion-body">
              <div class="audio-slider-container">
                <button type="button" class="audio-mute-btn" id="mute-btn-wrapper" aria-label="Mute">
                  <i id="mute" class="fa fa-volume-up fa-lg"></i>
                </button>
                <input type="range" id="volume-bar" class="neon-range-slider" min="0" max="1" step="0.05" value="1">
              </div>
            </div>
          </div>
          <div class="module-separator"></div>
          <?php endif; ?>

          <!-- Module 2: Present Participants -->
          <div class="control-module module-participants open" id="accordion-participants">
            <div class="accordion-header" onclick="toggleAccordion('accordion-participants')">
              <div class="accordion-header-left">
                <div class="module-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                </div>
                <span class="module-title"><?php echo defined('PRESENT_PARTICIPANTS') ? PRESENT_PARTICIPANTS : 'PARTICIPANTES PRESENTES'; ?> <span class="mobile-only-count"><?php echo !empty($dummy_user_qu) ? '(' . count($dummy_user_qu) . ')' : ''; ?></span></span>
              </div>
              <div class="accordion-chevron">
                <i class="fa fa-chevron-down"></i>
              </div>
            </div>
            <div class="accordion-body">
              <div class="participants-box">
                <div id="user_exists" style="display:none;"><?php echo count($dummy_user_qu); ?></div>
                <span class="user-count" style="display:none;"></span>
                <ul class="user-list">
                  <span class="User_list">
                    <?php
                    if (!empty($dummy_user_qu)) {
                      foreach ($dummy_user_qu as $dummy_user) {
                        echo '<li>' . $dummy_user['name'] . '</li>';
                      }
                    }
                    ?>
                  </span>
                  <span class="Online_User_list"></span>
                </ul>
              </div>
            </div>
          </div>

          <div class="module-separator"></div>

          <!-- Module 3: Send Your Question (Q&A Form) -->
          <div class="control-module module-qa open" id="accordion-qa">
            <div class="accordion-header" onclick="toggleAccordion('accordion-qa')">
              <div class="accordion-header-left">
                <div class="module-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </div>
                <span class="module-title"><?php echo defined('SEND_YOUR_QUESTION') ? SEND_YOUR_QUESTION : 'ENVÍA TU PREGUNTA'; ?></span>
              </div>
              <div class="accordion-chevron">
                <i class="fa fa-chevron-down"></i>
              </div>
            </div>
            <div class="accordion-body">
              <div class="module-subtitle">
                <?php echo defined('LIVE_WEBINAR_VALIDATION_MESSAGE') ? LIVE_WEBINAR_VALIDATION_MESSAGE : 'Introduce tu nombre y correo electrónico para que podamos enviarte nuestra respuesta'; ?>
              </div>
              <div class="enquiry-form-wrapper">
                <form id="enquiryForm">
                  <input type="hidden" id="webinar_token" name="token" value="<?php echo $newToken; ?>">
                  <input type="hidden" id="webinar_admin_email" name="webinar_admin_email" value="<?php echo $webinar_admin_email ?>">
                  <input type="hidden" name="webinar_name" id="webid" value="<?php echo $webinar_name; ?>">

                  <div class="form-inputs-row">
                    <div class="form-input-group">
                      <input class="modern-form-input" type="text" name="users_name" id="vector-smart-object-copy-13" placeholder="<?php echo defined('YOUR_NAME') ? YOUR_NAME : 'TU NOMBRE'; ?> (<?php echo defined('REQUIRED') ? REQUIRED : 'Requerido'; ?>)" required>
                    </div>
                    <div class="form-input-group">
                      <input class="modern-form-input" type="email" name="users_email" id="vector-smart-object-copy-17-1" placeholder="<?php echo defined('YOUR_EMAIL') ? YOUR_EMAIL : 'TU E-MAIL'; ?> (<?php echo defined('REQUIRED') ? REQUIRED : 'Requerido'; ?>)" required>
                    </div>
                  </div>

                  <textarea class="modern-form-textarea" id="vector-smart-object-10" name="question" placeholder="<?php echo defined('MESSAGE') ? MESSAGE : 'Mensaje'; ?>" rows="2"></textarea>

                  <span class="succes-text" style="display:none;"><?php echo defined('MESSAGE_RECEIVED') ? MESSAGE_RECEIVED : 'Mensaje enviado con éxito'; ?></span>

                  <button type="button" onClick="submitEnquiry();" class="modern-submit-btn">
                    <span><?php echo defined('SEND_MESSAGE') ? SEND_MESSAGE : 'ENVIAR MENSAJE'; ?></span>
                    <img id="loader_enquiry" src="images/loader2.gif" style="display:none; width: 16px; height: 16px; margin-left: 6px;" />
                  </button>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>

  <!-- Desktop Floating Chat Edge Tab -->
  <div class="chat-edge-tab" onclick="toggleChatDrawer()">
    <div class="chat-edge-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    </div>
    <span class="chat-edge-text">Chat</span>
    <span class="chat-edge-arrow">&lt;</span>
  </div>

  <!-- Mobile Floating Circular Chat Button (FAB) -->
  <div class="chat-mobile-fab" onclick="toggleChatDrawer()">
    <svg viewBox="0 0 24 24" fill="currentColor">
      <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>
      <circle cx="8.5" cy="10" r="1.5"/>
      <circle cx="12" cy="10" r="1.5"/>
      <circle cx="15.5" cy="10" r="1.5"/>
    </svg>
    <span>Chat</span>
  </div>

  <!-- Chat Drawer Backdrop -->
  <div class="chat-drawer-backdrop" onclick="toggleChatDrawer()"></div>

  <!-- Off-Canvas Sliding Chat Drawer -->
  <div class="chat-drawer" id="chatDrawer">
    <div class="chat-drawer-header">
      <div class="chat-drawer-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00d2ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span>ONLINE CHAT</span>
      </div>
      <button class="chat-drawer-close" onclick="toggleChatDrawer()" aria-label="Close Chat">
        <i class="fa fa-times"></i>
      </button>
    </div>
    <div class="chat-drawer-body">
      <div id="chat-section">
        <div class="red-text" id="chat-banned-msg"></div>
        <?php
        if ($chat_online) {
          require_once("chat/index.php");
        } else {
          echo $chat_error_msg;
        }
        ?>
      </div>
    </div>
  </div>
  <script>
    /**
     *
     * Submit Enquiry Form
     */
    function submitEnquiry() {
      jQuery("#loader_enquiry").show();
      var users_name = jQuery('#vector-smart-object-copy-13').val();
      var users_email = jQuery('#vector-smart-object-copy-17-1').val();
      var question = jQuery('#vector-smart-object-10').val();
      var token = jQuery('#webinar_token').val();
      var webinar_admin_email = jQuery('#webinar_admin_email').val();

      jQuery.ajax({
        type: 'POST',
        url: 'enquiry.php',
        data: {
          users_name: users_name,
          users_email: users_email,
          question: question,
          token: token,
          webinar_admin_email: webinar_admin_email
        },
        success: function(data) {

          jQuery("#loader_enquiry").hide();
          if (data == "mail_sent") {
            jQuery(".succes-text").text("Mensaje enviado con �xito");
            jQuery(".succes-text").show();
            jQuery('#vector-smart-object-copy-13').val('');
            jQuery('#vector-smart-object-copy-17-1').val('');
            jQuery('#vector-smart-object-10').val('');
            setTimeout(function() {
              jQuery(".succes-text").hide();
            }, 5000)
          } else {
            jQuery(".succes-text").attr("class", "warning-text");
            jQuery(".warning-text").text(data);
            jQuery(".warning-text").show();
          }
        }
      });
    }

    <?php if ($webinar_type != "google_hangout") : ?>
      /**
       *
       * Video Loading
       */

      // Event listener for the click btn
      jQuery(document).on("click", "#access-btn", function() {

        // Set Video to the offset that has already played
        if (window.sessionStorage.getItem("playBackVideoTime")) {
          video.currentTime = window.sessionStorage.getItem("playBackVideoTime");
        }

        // If Full video already watche then set current time to end of video
        if (video.duration && video.currentTime >= video.duration) {
          video.currentTime = video.duration - .01; //  Add Offset to video as video has been watched by the user
        }

        jQuery("#overlay").remove();
        video.play();
      });

      function showAccessBtn() {
        console.log("can play now");
        jQuery("div#overlay").remove();
        loading("access_btn");
      }


      /*
       **
       *  ========== Loader =============
       */

      var loading = function(load_obj) {
        var over = '<div id="overlay">';
        if (load_obj == "loader") {
          over += '<img id="loading" src="images/loading.gif">';
        } else if (load_obj == "access_btn") {
          over += '<button id="access-btn" class="custom-btn custom-btn-success">INGRESAR AHORA/ ACCESS NOW</button>'
        }

        over += '</div>';

        jQuery(over).prependTo('body');
      };

      /**
       *
       * Check If user on IOS then diff behavior for video loading
       */


      function checkIfOniOS() {

        var iDevices = [
          'iPad Simulator',
          'iPhone Simulator',
          'iPod Simulator',
          'iPad',
          'iPhone',
          'iPod'
        ];

        var onOSX = false;

        while (iDevices.length) {
          if (navigator.platform === iDevices.pop()) {
            onOSX = true;
          }
        }

        return onOSX;
      }

      var VideoFormatError = "Formato de v�deo no compatible, Pruebe con otro navegador \\ \n Video Format Not Supported, Try another browser";
      var lastPlayable;

      var myTypes = new Array("video/mp4", "video/ogg", "video/mov", "video/webm");
      var mySrc = new Array("<?php echo $video_name ?>.mp4", "<?php echo $video_name ?>.mv4", "<?php
                                                                                                echo $video_name ?>.ogg", "<?php echo $video_name ?>.mov", "<?php echo $video_name ?>.webm");

      /*
       * Error In Playing Video
       */
      function errorFallback() {
        if (video.currentSrc.match(lastPlayable)) {
          jQuery("#overlay").remove();
          alert(VideoFormatError);
        }
      }

      function checkVideoPlayable() {

        var noPlayableTypes = "Your browser cannot play these movie types";
        var playAny = 0;

        for (var i = 0, len = myTypes.length; i < len; i++) {
          var canPlay = video.canPlayType(myTypes[i]);
          if ((canPlay == "maybe") || (canPlay == "probably")) {
            playAny = 1;
            lastPlayable = mySrc[i];
          }
        }

        if (playAny == 0) {
          console.log("playback format not found");
          errorFallback();
        }
      }

      /**
       *
       * Video Controls
       */

      window.onload = function() {
        var onOSX = checkIfOniOS();

        // If On IOS Apple device than show m4v video first else show mp4 video first 
        var video_html = '';

        if (onOSX) {
          video_html += '<source id="video-m4v" src="<?php echo $video_url ?>.m4v" type="video/mp4">\
        <source id="video-mp4" src="<?php echo $video_url ?>.mp4" type="video/mp4">';
        } else {
          video_html += '<source id="video-mp4" src="<?php echo $video_url ?>.mp4" type="video/mp4">\
        <source id="video-m4v" src="<?php echo $video_url ?>.m4v" type="video/mp4">';
        }

        video_html += '<source src="<?php echo $video_url ?>.mov" type="video/mov">\
      <source src="<?php echo $video_url ?>.ogg" type="video/ogg">\
      <source src="<?php echo $video_url ?>.webm" type="video/webm">\
       <object type="application/x-shockwave-flash" data="http://releases.flowplayer.org/swf/flowplayer-3.2.1.swf" width="640" height="360">\
       <param name="movie" value="http://releases.flowplayer.org/swf/flowplayer-3.2.1.swf" />\
       <param name="allowFullScreen" value="true" />\
       <param name="wmode" value="transparent" />\
       <param name="flashvars" value="file=\'<?php echo $video_url ?>.mp4\'" />\
        </object>\
        Your browser does not support the video format being used.';

        if (jQuery("#video").length == 1) {
          jQuery("#video").append(video_html)
        }

        // Video
        var video = document.getElementById("video");

        var myVideo = document.querySelector('video');
        myVideo.addEventListener("contextmenu", function(e) {
          e.preventDefault();
          e.stopPropagation();
        }, false);

        // hide the controls if they're visible
        if (myVideo.hasAttribute("controls")) {
          myVideo.removeAttribute("controls")
        }

        // play/pause functionality
        function playPause() {
          if (myVideo.paused)
            myVideo.play();
          else
            myVideo.pause();
        }

        // Check If Video can be played or not
        checkVideoPlayable();

        video.addEventListener("error", errorFallback(), true);

        // Access Video Btn
        var access_btn = jQuery("#access-btn");

        // Buttons
        var muteButton = document.getElementById("mute");

        // Sliders
        var volumeBar = document.getElementById("volume-bar");

        // Event listener for the mute button
        muteButton.addEventListener("click", function() {
          if (video.muted == false) {
            // Mute the video
            video.muted = true;
            jQuery(muteButton).attr("class", "fa fa-volume-off fa-lg");

          } else {
            // Unmute the video
            video.muted = false;
            jQuery(muteButton).attr("class", "fa fa-volume-up fa-lg");

          }
        });

        // Event listener for the volume bar
        volumeBar.addEventListener("change", function() {
          // console.log("Volume Control");
          // Update the video volume
          video.volume = volumeBar.value;
        });
        volumeBar.addEventListener("input", function() {
          video.volume = volumeBar.value;
        });

        // intercept and cancel requests for the context menu
        video.addEventListener("contextmenu", function(e) {
          e.preventDefault();
          e.stopPropagation();
        }, false);

        // hide the controls if they're visible
        if (video.hasAttribute("controls")) {
          video.removeAttribute("controls")
        }
      };

    <?php endif; ?>


    /**
     *
     * Tab closed or Refresh Actions, Destroy Session
     */

    jQuery(document).ready(function($) {
      startPollingForChat(); // Start Checking Chat status now

      // Run interval to check if configured time has passed after opening of the page.
      // If so, show the purchase button.
      delayedShowPurchaseButton();

      var webinar_token = '<?php echo $webinar_token; ?>';

      // ative user status call for tracking user

      jQuery.ajax({
        type: 'POST',
        url: 'active_user_status.php',
        data: {
          webinar_token: webinar_token
        },
        success: function(data) {
          return true;
        }
      });

      // User About to leave the page, assumed, if not then active call will retain the user

      jQuery(window).bind("beforeunload", function(event) {
        <?php if ($webinar_type != "google_hangout") : ?>
          var playBackVideoTime = video.currentTime;
          window.sessionStorage.setItem("playBackVideoTime", video.currentTime);
        <?php endif; ?>
        jQuery.ajax({
          type: 'POST',
          url: 'user_status_deactive.php',
          async: false,
          data: {
            webinar_token: webinar_token
          }
        });

      });


    });

    function link_report(event) {
      var webinar_id = '<?php echo $webinar_id; ?>';
      var webinar_admin_id = '<?php echo filter_var($_GET["admin_id"], FILTER_SANITIZE_NUMBER_INT); ?>';
      var webinar_token = '<?php echo $webinar_token; ?>';
      var webinar_link = jQuery('.link_report').attr('title');
      jQuery.ajax({
        type: 'POST',
        url: 'webinar_link_report.php',
        data: {
          webinar_id: webinar_id,
          webinar_admin_id: webinar_admin_id,
          webinar_token: webinar_token
        },
        success: function(data) {
          return;
        }
      });
    }
  </script>
  <script>
    if (window.WebSocket) {
      console.log("BROWSER SUPPORTED");
    } else {
      jQuery("#chat-section").empty();
      jQuery("#chat-section").append("<div class='red-text' id='chat-banned-msg'>ONLINE CHAT (APAGADO/ OFF)</div>");
    }

    // Check If cookie sare enabled
    if (!navigator.cookieEnabled) {
      alert("Este sitio web utiliza cookies, debe habilitarlo para utilizar las funciones \n" +
        "This website uses cookies, you must enable it to use the features.");
    }
    jQuery('#submitButton').val('<?php echo SEND; ?>');
    jQuery('#headline').html('<?php echo CHAT_TITLE; ?>');


    var webinar_user_name = "<?php echo $webinar_user_name ?>";
    jQuery(document).ready(function() {
      jQuery("#webinar_user_name").append(webinar_user_name);
    });


    window.onerror = function(errorMessage, errorUrl, errorLine) {
      jQuery.ajax({
        type: 'POST',
        url: 'chat/error_report.php',
        data: {
          msg: errorMessage,
          url: errorUrl,
          line: errorLine,
          userAgent: navigator.userAgent
        },
        success: function() {
          // if (console && console.log) {
          //     console.log('JS error report successful.');
          // }
        },
        error: function() {
          // if (console && console.error) {
          //     console.error('JS error report submission failed!');
          // }
        }
      });

      // Prevent firing of default error handler.
      return true;
    }

    window.addEventListener("unload", function() {

      // get token from URL
      const params = new URLSearchParams(window.location.search);
      const token = params.get("token");

      const data = {
        webinar_token: token
      };

      navigator.sendBeacon(
        "https://vioniko.com/webinar-reg/ajaxload.php?action=user_left&token=" + token,
        JSON.stringify(data)
      );
    });

    /**
     * UI Redesign Helper Functions
     */
    function toggleChatDrawer() {
      var drawer = document.getElementById('chatDrawer');
      var backdrop = document.querySelector('.chat-drawer-backdrop');
      if (drawer) {
        drawer.classList.toggle('open');
      }
      if (backdrop) {
        backdrop.classList.toggle('active');
      }
    }

    function toggleAccordion(id) {
      if (window.innerWidth <= 991) {
        var el = document.getElementById(id);
        if (el) {
          el.classList.toggle('open');
        }
      }
    }
  </script>

</body>

</html>