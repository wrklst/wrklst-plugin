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

    public function adminSettings()
    {
        return require_once $this->plugin_path.'templates/settings.php';
    }

    public function wrklstOptionsGroup($input)
    {
        $options = get_option('wrklst_options');
        if ($input['api']) $options['api'] = $input['api']; else $options['api'] = '';
        if ($input['account']) $options['account'] = $input['account']; else $options['account'] = '';
        if ($input['cptartist']) $options['cptartist'] = $input['cptartist']; else $options['cptartist'] = 0;
        if ($input['cptexhibition']) $options['cptexhibition'] = $input['cptexhibition']; else $options['cptexhibition'] = 0;
        if ($input['cptartfair']) $options['cptartfair'] = $input['cptartfair']; else $options['cptartfair'] = 0;
        if ($input['wlbiowebhook']) $options['wlbiowebhook'] = $input['wlbiowebhook']; else $options['wlbiowebhook'] = 0;
        if ($input['musformatbio']) $options['musformatbio'] = $input['musformatbio']; else $options['musformatbio'] = '<p><span class="wl-bio-header" style="color: #000000; text-transform: uppercase;"><strong>{{artist.display}}</strong></span></p>
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
        if ($input['musformatnews']) $options['musformatnews'] = $input['musformatnews']; else $options['musformatnews'] = '{{#news}}
<p><span style="color: #000000;"><strong>{{display}}</strong></span></p>
{{#items}}
{{#year_display}}
<p style="text-align: right;"><strong>{{year_display}}</strong></p>{{/year_display}}
<p>{{caption}}{{#link}} (<a href="{{link}}" target="_blank">Link</a>){{/link}}</p>
{{/items}}
<p> </p>
{{/news}}';
        if ($input['whapikey']) $options['whapikey'] = $input['whapikey']; else $options['whapikey'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 32);



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

        echo '<input type="password" class="regular-text" name="wrklst_options[api]" value="'.esc_attr($options['api']).'" placeholder="Enter your api key" autocomplete="off" />';
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

        echo '<textarea name="wrklst_options[musformatbio]" rows="12" cols="50">'.($options['musformatbio']).'</textarea>';
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

        echo '<textarea name="wrklst_options[musformatnews]" rows="12" cols="50">'.($options['musformatnews']).'</textarea>';
    }

    public function wrklstWebhookApi()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['whapikey']))
        {
            $options = [];
            $options['whapikey'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 32);
        }
        echo '<input type="text" class="regular-text" style="width: 100%; max-width:580px;" name="wrklst_options[whapikey]" value="'.($options['whapikey']?esc_attr($options['whapikey']):substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 32).substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 32)).'" placeholder="Define Bio Webhook Authentication Token" autocomplete="off" /><br /><br />
        Please go to '
        .(isset($options['account'])&&$options['account']?'<a href="https://'.$options['account'].'.wrklst.com/settings/generalaccount" target="_blank">':'')
            .'https://'.(isset($options['account'])&&$options['account']?$options['account']:'[your personal account slug]').'.wrklst.com/settings/generalaccount'
        .(isset($options['account'])&&$options['account']?'</a>':'').' and then to "Webhook Sync". <bR />
        Activate "Sync Biographies via Webhook with your webpage" and enter the following URL as well as your Bio Webhook Authentication Token:<br />'.get_site_url().'/?webhook-listener=wl-biography<br /><bR /><a href="/wp-admin/edit.php?post_type=wlbiography">Crated Biography Content Pages</a><br /><br />Example shortcode to include Biography in Wordpress: <strong>[wrklst_bio_content id=123]</strong> (id is the artist\'s id in WrkLst)<br /><br />';
    }
}
