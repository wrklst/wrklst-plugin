<?php

$wrklst_images_gallery_languages = array('en' => 'English');

add_action('admin_menu', 'wrklst_images_add_settings_menu');
function wrklst_images_add_settings_menu() {
    add_options_page(__('WrkLst Settings', 'wrklst_images'), __('WrkLst', 'wrklst_images'), 'manage_options', 'wrklst_images_settings', 'wrklst_images_settings_page');
    add_action('admin_init', 'register_wrklst_images_options');
}


function register_wrklst_images_options(){
    register_setting('wrklst_images_options', 'wrklst_images_options', 'wrklst_images_options_validate');
    add_settings_section('wrklst_images_options_section', '', '', 'wrklst_images_settings');
    add_settings_field('language-id', __('Language', 'wrklst_images'), 'wrklst_images_render_language', 'wrklst_images_settings', 'wrklst_images_options_section');
    add_settings_field('account-id', __('Account ID', 'wrklst_images'), 'wrklst_images_render_account', 'wrklst_images_settings', 'wrklst_images_options_section');
    add_settings_field('api-id', __('API Key', 'wrklst_images'), 'wrklst_images_render_api', 'wrklst_images_settings', 'wrklst_images_options_section');
    add_settings_field('attribution-id', __('Attribution', 'wrklst_images'), 'wrklst_images_render_attribution', 'wrklst_images_settings', 'wrklst_images_options_section');
    add_settings_field('button-id', __('Button', 'wrklst_images'), 'wrklst_images_render_button', 'wrklst_images_settings', 'wrklst_images_options_section');
}


function wrklst_images_render_language(){
    global $wrklst_images_gallery_languages;
    $options = get_option('wrklst_images_options');
    $set_lang = substr(get_locale(), 0, 2);
    if (!$options['language']) $options['language'] = $wrklst_images_gallery_languages[$set_lang]?$set_lang:'en';
    echo '<select name="wrklst_images_options[language]">';
    foreach ($wrklst_images_gallery_languages as $k => $v) { echo '<option value="'.$k.'"'.($options['language']==$k?' selected="selected"':'').'>'.$v.'</option>'; }
    echo '</select>';
}

function wrklst_images_render_attribution(){
    $options = get_option('wrklst_images_options');
    echo '<label><input name="wrklst_images_options[attribution]" value="true" type="checkbox"'.(!$options['attribution'] | $options['attribution']=='true'?' checked="checked"':'').'> '.__('Insert image captions', 'wrklst_images').'</label>';
}

function wrklst_images_render_button(){
    $options = get_option('wrklst_images_options');
    echo '<label><input name="wrklst_images_options[button]" value="true" type="checkbox"'.(!$options['button'] | $options['button']=='true'?' checked="checked"':'').'> '.__('Show WrkLst button next to "Add Media"', 'wrklst_images').'</label>';
}

function wrklst_images_render_api(){
    $options = get_option('wrklst_images_options');
    echo '<input name="wrklst_images_options[api]" value="'.($options['api']).'" type="password"> ';
}

function wrklst_images_render_account(){
    $options = get_option('wrklst_images_options');
    echo '<input name="wrklst_images_options[account]" value="'.($options['account']).'" type="text"> ';
}


function wrklst_images_settings_page() { ?>
    <div class="wrap">
    <h2><?= _e('WrkLst', 'wrklst_images'); ?></h2>
    <form method="post" action="options.php">
        <?php
            settings_fields('wrklst_images_options');
            do_settings_sections('wrklst_images_settings');
            submit_button();
        ?>
    </form>
    </div>
<?php }


function wrklst_images_options_validate($input){
    global $wrklst_images_gallery_languages;
    $options = get_option('wrklst_images_options');
    if ($wrklst_images_gallery_languages[$input['language']]) $options['language'] = $input['language'];
    if ($input['attribution']) $options['attribution'] = 'true'; else $options['attribution'] = 'false';
    if ($input['api']) $options['api'] = $input['api']; else $options['api'] = '';
    if ($input['account']) $options['account'] = $input['account']; else $options['account'] = '';
    if ($input['button']) $options['button'] = 'true'; else $options['button'] = 'false';
    return $options;
}
?>
