<?php defined("APP") or die(); // Settings Page
    error_reporting ( E_ALL ) ;
    $currentTmail = "";
    $tmailBT = "Inbox";
    $BT_class = "custom-text-danger font-weight-bold";
    if(isset($_POST['select_tmail'])) $currentTmail = $_POST['select_tmail'];
    if(isset($_POST['tmailBT']))$tmailBT = $_POST['tmailBT'];
    $records = [];
    $arrCheckeds = array();
    if ($currentTmail != "") {     
        if($_POST['action_type'] == "delete")
        {   
            try {
                    $url = $this->config["incoming"];
                    $port = $this->config["incoming_port"];
                    $email = $this->config["based_email"];
                    $password = $this->config["password"];
                    $hosturl = '{'.$url.':'.$port.'/imap/ssl}'.$tmailBT;
                    if($tmailBT == "SentMail") $hosturl = '{'.$url.':'.$port.'/imap/ssl}INBOX.Sent';
                    $mailbox = new PhpImap\Mailbox($hosturl, $email, $password, ROOT.'/content', 'UTF-8');
                    $arrCheckeds = explode(",", $_POST["checked_uid"]);
                    foreach($arrCheckeds as $uidone)
                    {
                        $mailbox->deleteMail($uidone);
                    }
                $mailsIds = $mailbox->searchMailbox('ALL');
            } catch(PhpImap\Exceptions\ConnectionException $ex) {
                echo "IMAP connection failed: " . $ex;
                die();
            }
        }
        $api_key = $this->config["API_KEY"];
        $api_url = $this->config["API_URL"];
        $created_email = "none";
        {
            $arr = explode("@", $currentTmail);
            $curUser = $arr[0];
            $curDomain = $this->config["tmail_domain"];
            $url = "{$api_url}/api/create?key={$api_key}&email={$curUser}&domain={$curDomain}";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code == 200)
            { 
                $created_email = $result;
            } else {
                exit("<H3 class='text-center' style='color: red; font-weight: 500; font-size: 22px'>" 
                    .$result . "</H3>");
            }
        }
        if ($created_email != "none")
        {
            $url = "{$api_url}/api/fetch?key={$api_key}&email={$created_email}";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code != 200) {
                exit("<H3 class='text-center' style='color: red; font-weight: 500; font-size: 22px'>" 
                    .$result . "</H3>");
            }
            $result = json_decode($result);
            if ($result->length > 0)
            {
                $arrResult = (array)$result;
                unset($arrResult["length"]);
                $arrKeys = array_keys($arrResult);
                $index = 0;
                $del = new DateTime();
                $del->modify('-1 day');
                foreach($arrResult as $oneMail) {
                    if (in_array($arrKeys[$index], $arrCheckeds)) {
                        continue;
                    }
                    $record = [];
                    $record['subject'] = $oneMail->subject;
                    $record['from'] = $oneMail->sender_name;
                    $record['to'] = $currentTmail;
                    $date = new DateTime($oneMail->time);
                    if ($date < $del) {
                        $arrTemp = explode("@", $currentTmail);
                        $this->tmail_delete(strtolower($arrTemp[0]));
                        continue;
                    }
                    $record['date'] = $oneMail->time;
                    $record['uid'] = $arrKeys[$index];
                    $attachments = $oneMail->attachments;
                    if(sizeof($attachments) > 0) {
                        $record['hasattachment'] = 1;
                        $record['attachment'] = $attachments;
                    } else {
                        $record['hasattachment'] = 0;
                        $record['attachment'] = NULL;
                    }
                    $record['content'] = $oneMail->html;
                    $record['text'] = $oneMail->text;
                    $record['fromName'] = $oneMail->sender_name;
                    $record['fromAddress'] = $oneMail->sender_email;
                    array_push($records, $record);
                    ++$index;
                }
            }
        } else {
            exit("<H3 class='text-center' style='color: red; font-weight: 500; font-size: 22px'>" 
                    ."TMail creating error" . "</H3>");
        }
    }
?>

<div class="ajax"></div>
<div class="row">
    <div id="user-content" class="col-md-12">
        <?php echo $this->ads(728) ?>
        <?php echo Main::message() ?>
        <link href="<?php echo $this->config["url"] ?>/static/ubold/css/custom.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config["url"] ?>/static/ubold/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config["url"] ?>/static/ubold/css/app.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config["url"] ?>/static/ubold/libs/multiselect/multi-select.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config["url"] ?>/static/ubold/libs/select2/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config["url"] ?>/static/ubold/libs/summernote/summernote-bs4.css" rel="stylesheet" type="text/css" />
        <!-- Summernote css -->
        <div class="main-content panel panel-default panel-body">
            <div class="row col-12">
                <div class="col-md-4">
                   <h3><?php echo e("Temporary E-Mail Service") ?></h3>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <form action="<?php echo Main::href("user/tmails") ?>" method="Post" id="tmail_form">
                            <select onchange="javascript:change_tmail()" id="select_tmail" name = "select_tmail" class="form-control" data-toggle="select2" data-placeholder="<?php echo e("Select Temporary E-Mail") ?>">
                                <option value=""><?php echo e("Select Temporary E-Mail") ?></option>
                                <?php foreach ($tmails as $tmail){?>
                                <option value="<?php echo $tmail?>"
                                        <?php if($tmail == $currentTmail) echo "selected";?>><?php echo $tmail?></option>
                                <?php }?>
                            </select>
                            <input type="hidden" name="tmailBT" id = "tmailBT" value="<?php echo $tmailBT?>">
                            <input type="hidden" name="action_type" id="action_type" value="get">
                            <input type="hidden" name="checked_uid" id="checked_uid" value="">
                        </form>
                    </div>
                </div>
            </div>
            <hr>
            <div class="content" id="main_tmail" style="display: none">
                <!-- Start Content-->
                <div class="container-fluid">
                    <div class="row">
                        <!-- Right Sidebar -->
                        <div class="col-12">
                            <div class="card-box">
                                <!-- Left sidebar -->
                                <!-- <div class="inbox-leftbar">
                                    <a href="javascript: tmailBT('Compose')" class="btn btn-danger btn-block waves-effect waves-light"
                                       style="background-color: #f1556c !important">Compose</a>
                                    <div class="mail-list mt-4" id="tmailBtDiv">
                                        <a href="javascript: tmailBT('Inbox')" id="tmailInbox" class="list-group-item border-0 <?php if($tmailBT == "Inbox")echo $BT_class?>">
                                            <i class="mdi mdi-inbox font-18 align-middle mr-3"></i>
                                            Inbox
                                        </a>
                                        <a href="javascript: tmailBT('SentMail')" id="tmailSentMail" class="list-group-item border-0 <?php if($tmailBT == "SentMail")echo $BT_class?>">
                                            <i class="mdi mdi-send font-18 align-middle mr-3 mt-5"></i>
                                            Sent
                                        </a>
                                    </div>
                                </div> -->
                                <!-- End Left sidebar -->
                                <div class="inbox-rightbar" style="margin-left:0px; border-left: none; padding-left: 0px;"><!-- style="margin-left:0px; border-left: none; padding-left: 0px;"-->
                                    <div class="btn-group">
                                        <!-- <button onclick="compose_modal()" class="btn btn-primary pull-left">Compose</button> -->
                                        <button type="button" onclick="change_tmail()" class="btn btn-sm btn-light waves-effect ml-5"><i class="mdi mdi-refresh font-18"></i></button>
                                        <button type="button" onclick="delete_tmail()" class="btn btn-sm btn-light waves-effect ml-2"><i class="mdi mdi-delete-variant font-18"></i></button>
                                    </div>
                                    <?php
                                    if($tmailBT == "Inbox") include "tmail_inbox.php";
                                    else if($tmailBT == "SentMail") include "tmail_sentmail.php";
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="ajax-crud-modal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #293441;">
                        <h4 class="modal-title" id="userCrudModal"></h4>
                        <button onclick="" id="btndownload" class="btn btn-success btn-sm pull-left">Download</button>
                        <button data-dismiss="modal" aria-hidden="true" class="btn btn-danger btn-sm pull-right close-modal">Close</button>
                    </div>
                    <div class="modal-body">                            
                        <div class="form-group">
                            <div class="col-sm-12">
                                <div class="mt-4">
                                    <h5 class="font-18" id="subject">Hello, Howa re you?</h5>
                                    <hr/>
                                    <div class="media mb-4" style="padding-top: 10px;">
                                        <img class="d-flex mr-2 rounded-circle avatar-sm" src="<?php echo $this->config["url"] ?>/static/ubold/images/users/avatar.png" alt="Generic placeholder image">
                                        <div class="media-body">
                                            <span class="float-right" id="date">07:23 AM</span>
                                            <h6 class="m-0 font-14" id="fromName">Jonathan Smith</h6>
                                            <small class="text-muted" id="fromAddress">From: jonathan@domain.com</small>
                                        </div>
                                    </div>
                                    <div id="content" style="width: 100%; height: 200px; margin: 0px 15px 0px 20px;"></div>
                                    <hr/>
                                    <h6> <i class="fa fa-paperclip mb-2"></i> Attachments (<span id="attachment_count">0</span>) </h6>
                                    <div class="row" id="attachment_wdg" style="padding-left: 10px;">
                                        
                                    </div>
                                </div> <!-- card-box -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="compose-modal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #293441;">
                        <h4 class="modal-title">Email</h4>
                        <button data-dismiss="modal" aria-hidden="true" class="btn btn-danger btn-sm pull-right close-modal">Close</button>
                    </div>
                    <div class="modal-body">                            
                        <div class="form-group">
                            <div class="col-sm-12">
                                <div class="mt-4">
                                    <form>
                                        <div class="form-group">
                                            <input type="email" class="form-control" placeholder="To">
                                        </div>

                                        <div class="form-group">
                                            <input type="text" class="form-control" placeholder="Subject">
                                        </div>
                                        <div class="form-group">
                                            <div class="summernote">
                                                <h6>Hello Summernote</h6>
                                                <ul>
                                                    <li>
                                                        Select a text to reveal the toolbar.
                                                    </li>
                                                    <li>
                                                        Edit rich document on-the-fly, so elastic!
                                                    </li>
                                                </ul>
                                                <p>
                                                    End of air-mode area
                                                </p>
                                            </div>
                                        </div>
                                        <div class="form-group m-b-0">
                                            <div class="text-right">
                                                <button type="button" class="btn btn-success waves-effect waves-light m-r-5"><i class="mdi mdi-content-save-outline"></i></button>
                                                <button type="button" class="btn btn-success waves-effect waves-light m-r-5"><i class="mdi mdi-delete"></i></button>
                                                <button class="btn btn-primary waves-effect waves-light"> <span>Send</span> <i class="mdi mdi-send ml-2"></i> </button>
                                            </div>
                                        </div>
                                    </form>
                                </div> <!-- card-box -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Vendor js -->
        <script src="<?php echo $this->config["url"] ?>/static/ubold/js/vendor.min.js"></script>

        <!-- Inbox init -->
        <script src="<?php echo $this->config["url"] ?>/static/ubold/js/pages/inbox.js"></script>
        <!--Summernote js-->
        <script src="<?php echo $this->config["url"] ?>/static/ubold/libs/multiselect/jquery.multi-select.js"></script>
        <script src="<?php echo $this->config["url"] ?>/static/ubold/libs/select2/select2.min.js"></script>
        <!-- Init js-->
        <script src="<?php echo $this->config["url"] ?>/static/ubold/js/pages/form-advanced.init.js"></script>

        <script src="<?php echo $this->config["url"] ?>/static/ubold/libs/summernote/summernote-bs4.min.js"></script>
    </div><!--/#user-content-->
</div>
<script>
    function compose_modal() {
        $('#compose-modal').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
    function change_tmail() {
        if($("#select_tmail").val() != "") {
            $("#current_tmail").val($("#select_tmail").val());
            $("#main_tmail").css("display", "block");
            $("#tmail_form").submit();
        }
        else $("#main_tmail").css("display", "none");
    }
    function open_mail(index) {
        let records = <?php echo json_encode($records) ?>;
        let record = records[index];
        let attachment = record.attachment;
        let subject = record.subject;
        let date = record.date;
        let fromName = record.fromName;
        let fromAddress = record.fromAddress;
        let content = record.content;
        $("#subject").text(subject);
        $("#date").text(date);
        $("#fromName").text(fromName);
        $("#fromAddress").text("From: " + fromAddress);
        $("#content").html(content);
        let attachment_count = 0;
        $("#attachment_wdg").html("");
        if(parseInt(record.hasattachment) == 1)
        {
            for (const ind in attachment) {
                let oneattach = attachment[ind];
                let img = $('<img alt="attachment" width="50px" class="img-thumbnail img-responsive" />')
                            .attr("src", "<?php echo $this->config["url"] ?>/static/img/download.png");
                let ahref = $('<a target="_NEW"></a>').attr("href", oneattach.path).attr("title", oneattach.name).append(img);
                let col_2 = $('<div class="ml-2"></div>').append(ahref);
                $("#attachment_wdg").append(col_2);
                ++attachment_count;
            }
        }
        $("#attachment_count").text(attachment_count);
        $("#btndownload").attr("onclick", "download(" + index + ")");
        $('#ajax-crud-modal').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
    function delete_tmail() {
        if($("#select_tmail").val() != "") {
            let uids = "";
            $(".checkbox-wrapper-mail>input:checked").each(function(i, e){
                let uid = $(e).attr("uid");
                uids += (uids==""?uid:","+uid);
            });
            if (uids == "") {
                return;
            }
            if(!confirm("Would you really delete checked emails?"))
                return;
            $("#current_tmail").val($("#select_tmail").val());
            $("#main_tmail").css("display", "block");
            $("#action_type").val("delete");
            $("#checked_uid").val(uids);
            $("#tmail_form").submit();
        }
        else $("#main_tmail").css("display", "none");
    }

    function tmailBT(val) {
        $("#tmailBT").val(val);
        $("#tmail_form").submit();
    }
    function downloadURI(uri, name) {
        var link = document.createElement("a");
        link.download = name;
        link.href = uri;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        delete link;
    }
    function download(index)
    {
        let records = <?php echo json_encode($records) ?>;
        let record = records[index];
        let uid = record.uid;
        let tmailBT = "<?php echo $tmailBT ?>";
        $.ajax({
            type: "POST",
            url: "<?php echo Main::href("user/tmails") ?>",
            data: {action_type: "download", uid: uid, tmailBT: tmailBT},
            dataType:"text",
            beforeSend: function() {
                $('.modal-dialog').css("display", "none");
                $('.ajax').html("<img class='loader' src='<?php echo $this->config["url"] ?>/static/loader.gif' style='margin:5px 50%;border:0;' />");            
            },
            complete: function() {
                $('.modal-dialog').css("display", "block");
                $('.ajax').find('.loader').fadeOut("fast");
            },
            success: function (filepath) {
                downloadURI("<?php echo $this->config["url"] ?>" + filepath, record.subject + ".eml");
            }
        });  
    }
    $(document).ready(function(){
        $("#tmail").addClass("active");
        if($("#select_tmail").val() != "") $("#main_tmail").css("display", "block");
        $('.summernote').summernote({
            height: 230,                 // set editor height
            minHeight: null,             // set minimum height of editor
            maxHeight: null,             // set maximum height of editor
            focus: false                 // set focus to editable area after initializing summernote
        });
        // $.ajax({
        //     type: "GET",
        //     url: "http://localhost:8081/api/fetch?key=ttt",
        //     // dataType:"json",
        //     success: function (result) {
        //         console.log(result);
        //         alert();
        //     },
        //     error: function(XMLHttpRequest, textStatus, errorThrown) { 
        //         if (XMLHttpRequest.status == 0) {
        //         alert(' Check Your Network.');
        //         } else if (XMLHttpRequest.status == 404) {
        //         alert('Requested URL not found.');
        //         } else if (XMLHttpRequest.status == 500) {
        //         alert('Internel Server Error.');
        //         }  else {
        //         alert('Unknow Error.\n' + XMLHttpRequest.responseText);
        //         }     
        //     }
        // });  
    });
</script>