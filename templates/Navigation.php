<?php
$aria2_running = $_['aria2_running'];
$youtube_installed = $_['youtube_installed'];
$aria2_installed = $_['aria2_installed'];

?>
<div id="app-navigation">
    <div class="app-navigation-new" id="new-download"  data-inputbox="form-input-wrapper">
        <button type="button" class="icon-add">
            <?php print($l->t('Add Download'));?>
        </button>
    </div>
    <div class="app-navigation-new" id="new-download-ytdl" data-inputbox="form-input-wrapper">
        <button type="button" class="icon-add <?php $youtube_installed ? print "installed" : print "notinstalled";?>">
            <?php $youtube_installed ? print($l->t('YTDL Download')) : print $l->t('Youtube-dl NOT installed!');?>
        </button>
    </div>
    <div class="app-navigation-new" id="torrent-search-button"  data-inputbox="form-input-wrapper">
        <button type="button" class="icon-search">
            <?php print $l->t('Search torrents');?>
        </button>
    </div>
    <div class="app-navigation-new" id="start-aria2">
        <?php if ($aria2_installed): ?>
        <button type="button" class="icon-power"
            data-aria2="<?php $aria2_running ? print $l->t('on') : print $l->t('off');?>">
            <?php $aria2_running ? print $l->t('Stop Aria2') : print $l->t('Start Aria2');?>
        </button>
        <?php else: ?>
        <button type="button" class="icon-power notinstalled"
            data-aria2="<?php $aria2_running ? print $l->t('on') : print $l->t('off');?>">
            <?php print $l->t('Aria2 is not installed!');?>
        </button>
        <?php endif;?>
    </div>
    <div class="app-navigation-new" id="ncdownloader-user-settings">
        <button type="button" class="icon-settings" path="/settings/user/ncdownloader">
                <?php print $l->t('Settings');?>
        </button>
    </div>
    <ul>
        <li class="active-downloads">
            <div class="app-navigation-entry-bullet"></div>
            <a href="/apps/ncdownloader/dl/active">
                <?php print($l->t('Active Downloads'));?>
            </a>
            <div class="app-navigation-entry-utils">
                <ul>
                    <li class="app-navigation-entry-utils-counter" id="active-downloads-counter">
                        <div class="number">0</div>
                    </li>
                </ul>
            </div>
        </li>
        <li class="waiting-downloads">
            <div class="app-navigation-entry-bullet"></div>
            <a href="/apps/ncdownloader/dl/waiting">
                <?php print($l->t('Waiting Downloads'));?>
            </a>
            <div class="app-navigation-entry-utils">
                <ul>
                    <li class="app-navigation-entry-utils-counter" id="waiting-downloads-counter">
                        <div class="number">0</div>
                    </li>
                </ul>
            </div>
        </li>
        <li class="complete-downloads">
            <div class="app-navigation-entry-bullet"></div>
            <a href="/apps/ncdownloader/dl/complete">
                <?php print($l->t('Complete Downloads'));?>
            </a>
            <div class="app-navigation-entry-utils">
                <ul>
                    <li class="app-navigation-entry-utils-counter" id="complete-downloads-counter">
                        <div class="number">0</div>
                    </li>
                </ul>
            </div>
        </li>
        <li class="fail-downloads">
            <div class="app-navigation-entry-bullet"></div>
            <a href="/apps/ncdownloader/dl/fail">
                <?php print($l->t('Failed Downloads'));?>
            </a>
            <div class="app-navigation-entry-utils">
                <ul>
                    <li class="app-navigation-entry-utils-counter" id="fail-downloads-counter">
                        <div class="number">0</div>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</div>