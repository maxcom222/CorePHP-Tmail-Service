<div class="mt-3">
    <ul class="message-list">
    <?php
        if(!empty($records))
        {
            $i = -1;
            foreach($records as $record)
            {
                ?>
                <li class="<?php if (intval($record['seen']) == 0) echo "unread" ?>">
                    <div class="col-mail col-mail-1">
                        <div class="checkbox-wrapper-mail">
                            <input type="checkbox" uid="<?php echo $record['uid'] ?>" id="<?php echo "chk_".$record['uid'] ?>">
                            <label for="<?php echo "chk_".$record['uid'] ?>" class="toggle"></label>
                        </div>
                        <!-- <span class="star-toggle far fa-star text-warning"></span> -->
                        <a href="javascript:open_mail(<?php echo ++$i; ?>)" class="title" style="left: 70px;"><?php echo $record['from'] ?></a>
                    </div>
                    <div class="col-mail col-mail-2">
                        <a href="javascript:open_mail(<?php echo $i; ?>)" class="subject" style="right: 160px;"><?php echo $record['subject'] ?> &nbsp;&ndash;&nbsp;
                            <span class="teaser"><?php echo $record['text'] ?></span>
                        </a>
                        <div class="date" style="width: 150px;"><?php echo $record['date'] ?></div>
                    </div>
                </li>
                <?php
            }
        }
    ?>
    </ul>
</div>
<!-- end .mt-4 -->

<!-- <div class="row">
    <div class="col-sm-7 mt-1">
        Showing 1 - 20 of 289
    </div>
    <div class="col-sm-5 text-right">
        <div class="btn-group float-right">
            <button type="button" class="btn btn-light btn-sm"><i class="mdi mdi-chevron-left"></i></button>
            <button type="button" class="btn btn-info btn-sm"><i class="mdi mdi-chevron-right"></i></button>
        </div>
    </div>
</div> -->
<!-- end row-->