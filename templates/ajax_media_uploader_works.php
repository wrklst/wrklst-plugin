<?php
if ( !isset($wp_did_header) ) {
    $wp_did_header = true;
    require_once( dirname(__FILE__) . '/../../../../wp-load.php' );
    wp();
}

if(!is_user_logged_in())die('Not logged in.');
if( !current_user_can('editor') && !current_user_can('administrator') )die('No succifient rights.');


$wrklst_settings = get_option('wrklst_options');

?>
<style scope>
    ::-webkit-input-placeholder { color: #aaa !important; }
    ::-moz-placeholder { color: #aaa !important; }
    :-ms-input-placeholder { color: #aaa !important; }
    [placeholder] { text-overflow: ellipsis; }

    .flex-images { overflow: hidden; }
    .flex-images .item { margin: 4px; background: #f3f3f3; box-sizing: content-box; overflow: hidden; position: relative; }
    .flex-images .item > img { width: auto; height: auto; max-width: 100%; max-height: 100%; }
    .flex-images .item > .wrktitle { display: block; position:absolute; text-align: left; left:0; top: 0px; background: rgba(255,255,255,.80); color: #000; padding: 3px 5px 3px 5px;}

    .flex-images .item.exists > .wrktitle { background: rgba(242, 150, 150,.80); }
    .flex-images .item.existsp > .wrktitle { background: rgba(255, 220, 150,.80); }

    .flex-images .dlimg {
            opacity: 0; transition: opacity .3s; position: absolute; top: 0; right: 0; bottom: 0; left: 0;
            cursor: pointer; background: rgba(255,255,255,.80); color: #000;
            text-align: center; font-size: 14px; line-height: 1.5;
    }
    .hidden {display:none !important;}
    .flex-images .item:hover .dlimg, .flex-images .item.uploading .item.doneuploading .dlimg { opacity: 1; }
    .flex-images .item.uploading .dlimg { opacity: 1; }
    .flex-images .item.open .dlimg { opacity: 1; }
    .flex-images .item.doneuploading .dlimg { background: rgba(242, 254, 242,.80); }
    .flex-images .dlimg img { position: absolute; top: 30%; left: 0; right: 0; margin: auto; height: 70px; opacity: .2; }
    .flex-images .dlimg .caption { position: absolute; left: 0; right: 0; bottom: 15px; padding: 0 5px; text-align: left;}
    .flex-images .dlimg a { color: #eee; }
    .flex-images div.subitem > img { max-width: 90%; max-height: 90%; }
    .flex-images div.subitem { background: #ccc !important; text-align: center;}
    .flex-images div.breaker {
        background: rgba(242, 150, 150,1);
        height: 1px !important;
        min-width: 100% !important;
    }
    .flex-images .item.subitem .dlimg {
        border: 15px solid #ccc;
    }

    .flex-images .ender .dlimg {
        background: rgba(255, 255, 255,.0) !important;
    }

    img.hide-img {
        display:none;
    }

    #wrklst_settings_icon { opacity: .65; transition: .3s; box-shadow: none; }
    #wrklst_settings_icon:hover { opacity: 1; }

    .loading-rotator{
        transition-property: transform;
        animation-name: rotate;
        animation-duration: 1s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
    }

    @keyframes rotate {
        from {transform: rotate(0deg);}
        to {transform: rotate(360deg);}
    }

    .flex-images {
        display: flex;
        flex-wrap: wrap;
    }
    .flex-images .item {
        flex: 1 1 260px;
        height: 260px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .flex-images .item.ender .dlimg img {
        position: relative !important;
        top:35% !important;
        left: 7% !important;
    }
</style>

<div style="padding:10px 10px 10px 10px">
    <form id="wrklst_form" style="margin:0">
        <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
            <input id="search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search for e.g. Agnes Martin AM102">
            <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:1px;outline:0" title="Search"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-search-24px.svg' ?>" width="20px"></button>
        </div>
        <div style="margin:1em 0;padding-left:2px;line-height:2">
            <select id="filter_inventory" tsyle="display:inline-block;">
                <option value="all">
                    Any Inventory
                </option>
            </select>
            <label style="margin-left:15px;margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available">Available only</label>
        </div>
    </form>
    <div id="wrklst_results" class="flex-images" style="margin-top:15px;"></div>
</div>
