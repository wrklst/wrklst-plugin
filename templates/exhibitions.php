<?php
$wrklst_settings = get_option('wrklst_options');

?>
<h1 class="nav-tab-wrapper wp-clearfix">
    <a href="<?= esc_url(admin_url('admin.php?page=wrklst_works')) ?>" class="nav-tab">WrkLst Works</a>
    <a href="<?= esc_url(admin_url('admin.php?page=wrklst_exhibitions')) ?>" class="nav-tab nav-tab-active">Exhibitions</a>
</h1>
<?php
?>

<div id="wrklst-exhibitions-container" style="padding:10px 10px 10px 0px;">
    <div id="wrklst_exhibition_picker">
        <form id="wrklst_exh_form" style="margin:0">
            <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
                <input id="exh_search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search exhibitions, e.g. solo show 2023">
                <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:3px;outline:0" title="Search"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-search-24px.svg' ?>" width="20px"></button>
            </div>
            <div style="margin:1em 0;padding-left:2px;line-height:2">
                <a id="wrklst_settings_icon" href="admin.php?page=wrklst_settings"><img style="position:relative;top:5px" src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-settings-20px.svg' ?>" title="Settings" width="20px"></a>
            </div>
        </form>
        <div id="wrklst_exh_results" class="wrklst-exh-grid" style="margin-top:15px;"></div>
    </div>

    <div id="wrklst_exhibition_detail" class="hidden">
        <div style="margin:1em 0;line-height:2">
            <a href="#" id="wrklst_exh_back" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-arrow_back_ios-24px.svg' ?>" style="height:18px"> Back to exhibitions</a>
        </div>
        <div id="wrklst_exh_header" style="margin:1em 0"></div>
        <div id="wrklst_results" class="flex-images" style="margin-top:15px;"></div>
    </div>
</div>
