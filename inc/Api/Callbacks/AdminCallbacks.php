<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Api\Callbacks;
use \Inc\Base\BaseController;

class AdminCallbacks extends BaseController
{
    public function adminWorks()
    {
        return require_once $this->plugin_path.'templates/works.php';
    }

    public function adminExhibitions()
    {
        return require_once $this->plugin_path.'templates/exhibitions.php';
    }

    public function adminSettings()
    {
        return require_once $this->plugin_path.'templates/settings.php';
    }

    public function wrklstOptionsGroup($input)
    {
        $options = get_option('wrklst_options');
        $options = is_array($options) ? $options : [];
        $options['api'] = isset($input['api']) ? sanitize_text_field($input['api']) : '';
        // The account is a wrklst.com subdomain label. Anything else would send the API key to a foreign host.
        $options['account'] = isset($input['account']) ? strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $input['account'])) : '';
        if (isset($input['cptartist']) && $input['cptartist']) $options['cptartist'] = $input['cptartist']; else $options['cptartist'] = 0;
        if (isset($input['cptexhibition']) && $input['cptexhibition']) $options['cptexhibition'] = $input['cptexhibition']; else $options['cptexhibition'] = 0;
        if (isset($input['cptartfair']) && $input['cptartfair']) $options['cptartfair'] = $input['cptartfair']; else $options['cptartfair'] = 0;
        if (isset($input['wlbiowebhook']) && $input['wlbiowebhook']) $options['wlbiowebhook'] = $input['wlbiowebhook']; else $options['wlbiowebhook'] = 0;
        if (isset($input['musformatbio']) && $input['musformatbio']) $options['musformatbio'] = $input['musformatbio']; else $options['musformatbio'] = '<p><span class="wl-bio-header" style="color: #000000; text-transform: uppercase;"><strong>{{artist.display}}</strong></span></p>
{{#categories}}
{{#title}}
<p><span class="wl-bio-header" style="color: #000000;text-transform: uppercase;"><strong>{{title}}</strong></span></p>
{{/title}}
<dl class="artist-biography">
{{#items}}
<dt class="wl-bio-year">{{#year_display}}{{year_display}}{{/year_display}}</dt>
<dd class="wl-bio-caption">{{caption}}{{#link}} (<a href="{{link}}" target="_blank">Link</a>){{/link}}</dd>
{{/items}}
</dl>
{{/categories}}';
        if (isset($input['musformatnews']) && $input['musformatnews']) $options['musformatnews'] = $input['musformatnews']; else $options['musformatnews'] = '{{#news}}
<p><span style="color: #000000;"><strong>{{display}}</strong></span></p>
{{#items}}
{{#year_display}}
<p style="text-align: right;"><strong>{{year_display}}</strong></p>{{/year_display}}
<p>{{caption}}{{#link}} (<a href="{{link}}" target="_blank">Link</a>){{/link}}</p>
{{/items}}
<p> </p>
{{/news}}';
        $options['whapikey'] = isset($input['whapikey']) && $input['whapikey'] ? sanitize_text_field($input['whapikey']) : wp_generate_password(32, false);
        
        if (isset($input['workdcaptioninvnr']) && $input['workdcaptioninvnr']) $options['workdcaptioninvnr'] = $input['workdcaptioninvnr']; else $options['workdcaptioninvnr'] = 0;

        $allowed_formats = ['jpg', 'webp'];
        $options['imageformat'] = (isset($input['imageformat']) && in_array($input['imageformat'], $allowed_formats, true)) ? $input['imageformat'] : 'jpg';

        return $options;
    }

    public function wrklstAdminWlApiSection()
    {
        echo 'Enter your WrkLst API crendentials to connect this Wordpress Website with your WrkLst account.';
    }

    public function wrklstAdminWlApiSection2()
    {
        echo 'Define which Custom Post Types should be created through this plugin.';
    }

    public function wrklstAdminWlApiSection3()
    {
        echo 'Settigns for connecting WrkLst Biographies Module with this wordpress webpage.';
    }
    
    public function wrklstAdminWlApiSection4()
    {
        echo 'Settigns for Work Captions and what they include.';
    }

    public function wrklstAdminWlApiSection5()
    {
        echo 'Choose the image format delivered by imgproxy for media library thumbnails and uploads.';
    }

    public function wrklstAccountId()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['account']))
        {
            $options = [];
            $options['account'] = '';
        }

        echo '<input type="text" class="regular-text" name="wrklst_options[account]" value="'.esc_attr($options['account']).'" placeholder="Enter your account Id" autocomplete="off" /><span>.wrklst.com</span>';
    }

    public function wrklstApiKey()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['api']))
        {
            $options = [];
            $options['api'] = '';
        }

        $account = isset($options['account']) && $options['account'] ? $options['account'] : '';
        $tokens_url = 'https://'.($account ?: '[your account id]').'.wrklst.com/settings/apitokens';

        echo '<input type="password" class="regular-text" name="wrklst_options[api]" value="'.esc_attr($options['api']).'" placeholder="Paste your WordPress plugin token" autocomplete="off" />';
        echo '<p class="description">'
            .'Create a <strong>WordPress plugin</strong> token in WrkLst under Settings &rarr; API tokens'
            .($account ? ': <a href="'.esc_url($tokens_url).'" target="_blank" rel="noopener">'.esc_html($tokens_url).'</a>' : ' (enter your Account ID above and save to get the link)')
            .'. A WordPress plugin token shows works and exhibitions only &mdash; no prices, nothing confidential.'
            .'</p>';
    }

    public function wrklstCustomPostTypeArtist()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['cptartist']))
        {
            $options = [];
            $options['cptartist'] = 1;
        }

        echo '<input type="checkbox" value="1" id="cptartist" name="wrklst_options[cptartist]"'.(($options['cptartist'])?'checked':'').' />';
    }

    public function wrklstCustomPostTypeExhibition()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['cptexhibition']))
        {
            $options = [];
            $options['cptexhibition'] = 1;
        }

        echo '<input type="checkbox" value="1" id="cptexhibition" name="wrklst_options[cptexhibition]"'.(($options['cptexhibition'])?'checked':'').' />';
    }

    public function wrklstCustomPostTypeArtFair()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['cptartfair']))
        {
            $options = [];
            $options['cptartfair'] = 1;
        }

        echo '<input type="checkbox" value="1" id="cptartfair" name="wrklst_options[cptartfair]"'.(($options['cptartfair'])?'checked':'').' />';
    }

    public function wrklstActivateWlBioWebhook()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['wlbiowebhook']))
        {
            $options = [];
            $options['wlbiowebhook'] = 0;
        }

        echo '<input type="checkbox" value="1" id="wlbiowebhook" name="wrklst_options[wlbiowebhook]"'.(($options['wlbiowebhook'])?'checked':'').' />';
    }

    public function wrklstBioFormat()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['musformatbio']))
        {
            $options = [];
            $options['musformatbio'] = '<p><span class="wl-bio-header" style="color: #000000; text-transform: uppercase;"><strong>{{artist.display}}</strong></span></p>
{{#categories}}
{{#title}}
<p><span class="wl-bio-header" style="color: #000000;text-transform: uppercase;"><strong>{{title}}</strong></span></p>
{{/title}}
<dl class="artist-biography">
{{#items}}
<dt class="wl-bio-year">{{#year_display}}{{year_display}}{{/year_display}}</dt>
<dd class="wl-bio-caption">{{caption}}{{#link}} (<a href="{{link}}" target="_blank">Link</a>){{/link}}</dd>
{{/items}}
</dl>
{{/categories}}';
        }

        echo '<textarea name="wrklst_options[musformatbio]" rows="12" cols="50">'.esc_textarea($options['musformatbio']).'</textarea>';
    }

    public function wrklstNewsFormat()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['musformatnews']))
        {
            $options = [];
            $options['musformatnews'] = '{{#news}}
    <p><span style="color: #000000;"><strong>{{display}}</strong></span></p>
    {{#items}}
    {{#year_display}}
    <p style="text-align: right;"><strong>{{year_display}}</strong></p>{{/year_display}}
    <p>{{caption}}{{#link}} (<a href="{{link}}" target="_blank">Link</a>){{/link}}</p>
    {{/items}}
    <p> </p>
    {{/news}}';
        }

        echo '<textarea name="wrklst_options[musformatnews]" rows="12" cols="50">'.esc_textarea($options['musformatnews']).'</textarea>';
    }

    public function wrklstWebhookApi()
    {
        $options = get_option('wrklst_options');
        $options = is_array($options) ? $options : [];
        $account = !empty($options['account']) ? $options['account'] : '';
        $whapikey = !empty($options['whapikey']) ? $options['whapikey'] : wp_generate_password(32, false);
        $settings_url = 'https://'.($account ?: '[your personal account slug]').'.wrklst.com/settings/generalaccount';
        $listener_url = get_site_url().'/?webhook-listener=wl-biography';

        echo '<input type="text" class="regular-text" style="width: 100%; max-width:580px;" name="wrklst_options[whapikey]" value="'.esc_attr($whapikey).'" placeholder="Define Bio Webhook Authentication Token" autocomplete="off" /><br /><br />
        Please go to '
        .($account ? '<a href="'.esc_url($settings_url).'" target="_blank" rel="noopener">'.esc_html($settings_url).'</a>' : esc_html($settings_url))
        .' and then to "Webhook Sync". <br />
        Activate "Sync Biographies via Webhook with your webpage" and enter the following URL as well as your Bio Webhook Authentication Token:<br />'.esc_html($listener_url).'<br /><br /><a href="'.esc_url(admin_url('edit.php?post_type=wlbiography')).'">Created Biography Content Pages</a><br /><br />Example shortcode to include Biography in Wordpress: <strong>[wrklst_bio_content id=123]</strong> (id is the artist\'s id in WrkLst)<br /><br />';
    }
    
    public function wrklstWorkCaptionInvNr()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['workdcaptioninvnr']))
        {
            $options = [];
            $options['workdcaptioninvnr'] = 1;
        }

        echo '<input type="checkbox" value="1" id="workdcaptioninvnr" name="wrklst_options[workdcaptioninvnr]"'.(($options['workdcaptioninvnr'])?'checked':'').' />';
    }

    public function wrklstImageFormat()
    {
        $options = get_option('wrklst_options');
        $current = (isset($options['imageformat']) && in_array($options['imageformat'], ['jpg', 'webp'], true)) ? $options['imageformat'] : 'jpg';

        echo '<select id="imageformat" name="wrklst_options[imageformat]">'
            .'<option value="jpg"'.($current === 'jpg' ? ' selected' : '').'>JPEG</option>'
            .'<option value="webp"'.($current === 'webp' ? ' selected' : '').'>WebP</option>'
            .'</select>';
    }
}
