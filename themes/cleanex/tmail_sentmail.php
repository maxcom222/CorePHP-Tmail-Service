<div class="mt-3">
    <ul class="message-list">
    <?php
        if(!empty($records))
        {
            foreach($records as $record)
            {
                ?>
                <li class="<?php if (intval($record['seen']) == 0) echo "unread" ?>">
                    <div class="col-mail col-mail-1">
                        <div class="checkbox-wrapper-mail">
                            <input type="checkbox" id="<?php echo "chk_".$record['uid'] ?>">
                            <label for="<?php echo "chk_".$record['uid'] ?>" class="toggle"></label>
                        </div>
                        <!-- <span class="star-toggle far fa-star text-warning"></span> -->
                        <a href="" class="title" style="left: 70px;"><?php echo $record['to'] ?></a>
                    </div>
                    <div class="col-mail col-mail-2">
                        <a href="" class="subject" style="right: 160px;"><?php echo $record['subject'] ?> &nbsp;&ndash;&nbsp;
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