<?php
/*
TODO:
Mark works already existing based out of combination of id and image id
show multiple images per work.

*/
$wrklst_settings = get_option('wrklst_options');

echo '<h1>WrkLst Works</h1>';
?>

<div id="wrklst-works-container" style="padding:10px 10px 10px 0px;">
    <form id="wrklst_form" style="margin:0">
        <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
            <input id="search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search for e.g. Agnes Martin AM102">
            <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:3px;outline:0" title="Search"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-search-24px.svg' ?>" width="20px"></button>
        </div>
        <div style="margin:1em 0;padding-left:2px;line-height:2">
            <select id="filter_inventory" tsyle="display:inline-block;">
                <option value="all">
                    Any Inventory
                </option>
            </select>
            <label style="margin-left:15px;margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available">Available only</label>
            <a id="wrklst_settings_icon" href="admin.php?page=wrklst_settings"><img style="position:relative;top:5px" src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-settings-20px.svg' ?>" title="Settings" width="20px"></a>
        </div>
    </form>
    <div id="wrklst_results" class="flex-images" style="margin-top:15px;"></div>
</div>
