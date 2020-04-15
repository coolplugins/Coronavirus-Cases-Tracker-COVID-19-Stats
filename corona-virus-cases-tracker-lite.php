<?php
/**
 * Plugin Name:Corona Virus Cases Tracker - Covid-19 Stats
 * Description:Use this shortcode [cvct] and display Novel Coronavirus(COVID-19) outbreak live Updates in your Page,post or widget section 
 * Author:Cool Plugins
 * Author URI:https://coolplugins.net/
 * Plugin URI:https://cryptowidget.coolplugins.net/
 * Version:1.2
 * License: GPL2
 * Text Domain:cvct
 * Domain Path: languages
 *
 *@package Corona Virus Cases Tracker*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'CVCT_VERSION' ) ) {
	return;
}
/*
Defined constent for later use
*/
define( 'CVCT_VERSION', '1.2' );
define( 'CVCT_Cache_Timing', HOUR_IN_SECONDS );
define( 'CVCT_FILE', __FILE__ );
define( 'CVCT_DIR', plugin_dir_path( CVCT_FILE ) );
define( 'CVCT_URL', plugin_dir_url( CVCT_FILE ) );

/**
 * Class Corona Virus Cases Tracker
 */
final class Corona_Virus_Cases_Tracker_lite {

	/**
	 * Plugin instance.
	 *
	 * @var Corona_Virus_Cases_Tracker_lite
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Corona_Virus_Cases_Tracker
	 * @static
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * @access private
	 */
	private function __construct() {
		// register activation/ deactivation hooks
		register_activation_hook( CVCT_FILE, array( $this , 'cvct_activate' ) );
		register_deactivation_hook(CVCT_FILE, array($this , 'cvct_deactivate' ) );
		// load text domain for translation
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'cvct_includes' ) ); 
        //main plugin shortcode for list widget
         add_shortcode( 'cvct', array($this, 'cvct_shortcode' ));
         add_shortcode('cvct-tbl',array($this,'cvct_tbl_shortcode'));
	}

    /*
|--------------------------------------------------------------------------
| Load required files
|--------------------------------------------------------------------------
*/  
    public function cvct_includes() {
        if(is_admin()){
			require_once CVCT_DIR .'includes/cvct-feedback-notice.php';
			new CVCTFreeFeedbackNotice();
		}
    }
/*
|--------------------------------------------------------------------------
| Crypto Widget Main Shortcode
|--------------------------------------------------------------------------
*/ 
public function  cvct_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'title'=>'Global Stats',
        'country-code'=>'all',
        'label-total'=>'Total Cases',
        'label-deaths'=>'Deaths',
        'label-recovered'=>'Recovered',
        'bg-color'=>'#ddd',
        'font-color'=>'#000',
    ), $atts, 'cvct' );
    $countryCode=!empty($atts['country-code'])?$atts['country-code']:"all";
    $stats='';
    $output='';
    $tp_html='';
 	 $style='layout-1';
    $total_cases='';
    $total_recovered='';
    $total_deaths='';
    $title=$atts['title'];
    $label_total=$atts['label-total'];
    $label_deaths=$atts['label-deaths'];
    $label_recovered=$atts['label-recovered'];
    $style=!empty($atts['style'])?$atts['style']:"style-1";
    $bgColors=!empty($atts['bg-color'])?$atts['bg-color']:"#DDDDDD";
    $fontColors=!empty($atts['font-color'])?$atts['font-color']:"#000";
    $custom_style='';
    $custom_style .='background-color:'.$bgColors.';';
    $custom_style .='color:'.$fontColors.';';
    $stats_data='';
  
    if($countryCode=="all"){
        $stats_data=$this->cvct_g_stats_data();
    }else{
        $stats_data= $this->cvct_c_stats_data($countryCode);
        if(isset($stats_data['country'])){
            $title=$stats_data['country'].' '.$title;
        }
    }
  
   
   if(is_array($stats_data)&& count($stats_data)>0){
       $total=$stats_data['total_cases'];
       $recovered=$stats_data['total_recovered'];
       $deaths=$stats_data['total_deaths'];
        $total_cases=!empty($total)? number_format($total):"0";
        $total_recovered=!empty($recovered)?number_format($recovered):"0";
        $total_deaths=!empty($deaths)?number_format($deaths):"0";
   }
        $tp_html.='
        <div id="coronatracker-card" class="cvct-style1" style="'.esc_attr($custom_style).'">
            <h2 style="width:85%;'.esc_attr($custom_style).'">'.esc_html($title).'</h2>
            <div class="cvct-number">
                <span>'.esc_html($total_cases).'</span>
                <span>'.esc_html($label_total).'</span>
            </div>
            <div class="cvct-number">
                <span>'.esc_html($total_deaths).'</span>
                <span>'.esc_html($label_deaths).'</span>
            </div>
            <div class="cvct-number">
                <span>'.esc_html($total_recovered).'</span>
                <span>'.esc_html($label_recovered).'</span>
            </div>
        </div>';
    $css="<style>". esc_html($this->cvct_load_styles($style))."</style>";
    $output.='<div class="cvct-wrapper">'.$tp_html.'</div>';
    $cvctv='<!-- Corona Virus Cases Tracker - Version:- '.CVCT_VERSION.' By Cool Plugins (CoolPlugins.net) -->';	
    return  $cvctv.$output.$css;	
}


/*
|--------------------------------------------------------------------------
| fetch global stats
|--------------------------------------------------------------------------
*/  
public function cvct_g_stats_data(){
    $cache_name='cvct_gs';
     $cache=get_transient($cache_name);
     $cache=false;
    $gstats_data='';
    $save_arr=array();
if($cache==false){
         $api_url = 'https://corona.lmao.ninja/all';
         $request = wp_remote_get($api_url, array('timeout' => 120));
         if (is_wp_error($request)) {
             return false; // Bail early
         }
         $body = wp_remote_retrieve_body($request);
         $gt_data = json_decode($body,true);
         if(is_array($gt_data ) && isset($gt_data['cases'])){
            $save_arr['total_cases']=$gt_data['cases'];
            $save_arr['total_recovered']=$gt_data['recovered'];
            $save_arr['total_deaths']=$gt_data['deaths'];
            set_transient($cache_name,
            $save_arr,CVCT_Cache_Timing
             ); 
            update_option("cvct_gs_updated",date('Y-m-d h:i:s') );   
            $gstats_data=$save_arr;
                 return $gstats_data;
         }else{
         	return false;
         }
     }else{
     return $gstats_data=get_transient($cache_name);
     }
}


/*
|--------------------------------------------------------------------------
| fetch country stats
|--------------------------------------------------------------------------
*/  
public function cvct_c_stats_data($country_code){
    $cache_name='cvct_cs_'.$country_code;
    $cache=get_transient($cache_name);
    $cstats_data='';
    $save_arr=[];
   if($cache==false){
         $api_url = 'https://corona.lmao.ninja/countries/'.$country_code;
         $request = wp_remote_get($api_url, array('timeout' => 120));
         if (is_wp_error($request)) {
             return false; // Bail early
         }
         $body = wp_remote_retrieve_body($request);
         $cs_data = json_decode($body);
         if(isset($cs_data)&& !empty($cs_data)){
                $save_arr['total_cases']=$cs_data->cases;
               $save_arr['total_recovered']=$cs_data->recovered;
               $save_arr['total_deaths']=$cs_data->deaths;
               $save_arr['country']=$cs_data->country;
           set_transient($cache_name,
           $save_arr,CVCT_Cache_Timing);
            set_transient('api-source',
            'corona.lmao.ninja', CVCT_Cache_Timing);
             update_option("cvct_cs_updated",date('Y-m-d h:i:s') );   
             $cstats_data= $save_arr;
                 return $cstats_data;
         }else{
             return false;
         }
     }else{
       return $cstats_data=get_transient($cache_name);
     }
   }
   

/**
 * Table shortcode
 */
public function cvct_tbl_shortcode($atts, $content = null ){
    $atts = shortcode_atts( array(
        'id'  => '',
        'layout'=>'layout-1',
        'show' =>"10" ,
        'label-confirmed'=>"Confirmed",
        'label-deaths'=>"Death",
         'label-recovered'=>"Recovered",
         'label-active'=>'Active',
         'label-country'=>'Country',
        'bg-color'=>'#222222',
        'font-color'=>'#f9f9f9'
    ), $atts, 'cvct' );
    $style = !empty($atts['layout'])?$atts['layout']:'layout-1';
    $country = !empty($atts['label-country'])?$atts['label-country']:'Country';
    $confirmed = !empty($atts['label-confirmed'])?$atts['label-confirmed']:'Confirmed';
    $deaths = !empty($atts['label-deaths'])?$atts['label-deaths']:'Death';
    $recoverd = !empty($atts['label-recovered'])?$atts['label-recovered']:'Recovered';
    $active = !empty($atts['label-active'])?$atts['label-active']:'Active';
    $bgColors=!empty($atts['bg-color'])?$atts['bg-color']:"#222222";
    $fontColors=!empty($atts['font-color'])?$atts['font-color']:"#f9f9f9";
    $show_entry = !empty($atts['show'])?$atts['show']:'10';
    $cvct_html = '';
    $stack_arr = array();
    $results = array();
    $count = 0;
  $cvct_get_data = $this->cvct_get_all_country_data();
if(is_array($cvct_get_data)&& count($cvct_get_data)>0){
        $cvct_html.= '<table id="cvct_table_layout" class="table-layout-1">
        <thead><tr>
            <th>'.__($country,'cvct').'</th>
            <th>'.__($confirmed,'cvct').'</th>
            <th>'.__($recoverd,'cvct').'</th>
            <th>'.__($deaths,'cvct').'</th>
            </tr> </thead><tbody>';
    foreach($cvct_get_data as $cvct_stats_data){
            $total = $cvct_stats_data['cases'];
            $country_name = isset($cvct_stats_data['country'])?$cvct_stats_data['country']:'';
            $confirmed = $cvct_stats_data['confirmed'];
            $recoverd = $cvct_stats_data['recoverd'];
            $death = $cvct_stats_data['deaths'];
            $active = $cvct_stats_data['active'];
        
            $total_cases = !empty($total)?number_format($total):'0';
            $confirmed_cases = !empty($confirmed)?number_format($confirmed):'0';
            $recoverd_cases = !empty($recoverd)?number_format($recoverd):'0';
            $death_cases = !empty($death)?number_format($death):'0';
            $total_count =  $count++;
            if ($total_count == $show_entry) break;
            $title=$country_name;
            $i=1;
            $cvct_html.= '<tr class="cvct-style1-stats">';
            $cvct_html.= '<td class="cvct-country-title">'.$country_name.'</td>';
            $cvct_html.= '<td class="cvct-confirm-case">'.$confirmed_cases.'</td>
            <td class="cvct-recoverd-case">'.$recoverd_cases.'</td>
            <td class="cvct-death-case">'.$death_cases.'</td>
            </tr>';
    }
    $cvct_html.=  '</tbody>
    </table>';
  }else{
    $cvct_html.='<div>'.__('Something wrong With API').'</div>'; 
  }

  $css='<style>
  table#cvct_table_layout tr th, table#cvct_table_id tr th {background-color:'.$bgColors.';color:'.$fontColors.';}
table#cvct_table_layout tr td, table#cvct_table_id tr td {background-color:'.$fontColors.';color:'.$bgColors.';}
  '.$this->cvct_load_table_styles().'</style>';
  $cvctv='<!-- Corona Virus Cases Tracker - Version:- '.CVCT_VERSION.' By Cool Plugins (CoolPlugins.net) -->';
    return $cvctv. '<div  class="cvct-wrapper">' . $cvct_html . '</div>' .$css;
  
  }

/*
|--------------------------------------------------------------------------
| loading required assets according to the widget type
|--------------------------------------------------------------------------
*/  
    function cvct_load_styles($style){
        $css='#coronatracker-card, #coronatracker-card * {
            box-sizing: border-box;
        }
        #coronatracker-card h2 {
            margin: 0 0 10px 0;
            padding: 0;
            font-size: 20px;
            font-weight: bold;
        }.cvct-number {
            width: calc(33.33% - 3px);
            display: inline-block;
           
            padding: 8px 4px 15px;
            padding-top:25px;
            text-align: center;
        }
        .cvct-number span {
            width: 100%;
            display: inline-block;
            font-size: 14px;
        }
        .cvct-number span:first-child {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .cvct-style1 {
            width: 100%;
            max-width: 420px;
            display: inline-block;
            background: #ddd url('.CVCT_URL.'/assets/corona-virus.png);
            padding: 10px;
            border-radius: 8px;
			background-size: 75px;
			background-color:#ddd;
            background-position: right top;
            background-repeat: no-repeat;
            margin: 5px 0 10px;
        }';
     
        return $css;
    }

/*  
|--------------------------------------------------------------------------
| loading required assets according to the widget type
|--------------------------------------------------------------------------
*/  
    function cvct_load_table_styles(){
      $css = '';
          $css=' table#cvct_table_layout,
            table#cvct_states_table_id,
            table#cvct_table_id {
            table-layout: fixed;
            border-collapse: collapse;
            border-radius: 5px;
            overflow: hidden;
            }
            table#cvct_states_table_id tr th,
            table#cvct_states_table_id tr td,
            table#cvct_table_layout tr th,
            table#cvct_table_layout tr td,
            table#cvct_table_id tr th,
            table#cvct_table_id tr td {
            text-align: center;
            vertical-align: middle;
            font-size:14px;
            line-height:16px;
            text-transform:capitalize;
            border: 1px solid rgba(0, 0, 0, 0.15);
            width: 110px;
            padding: 12px 4px;
            }
            table#cvct_table_layout tr th:first-child,
            table#cvct_table_layout tr td:first-child,
            table#cvct_table_layout tr th:first-child,
            table#cvct_table_layout tr td:first-child {
            text-align: left;
            }
            table#cvct_table_layout tr td img {
            margin: 0 4px 2px 0;
            padding: 0;
            vertical-align: middle;
            }
            div#cvct_table_id_wrapper input,
            div#cvct_table_id_wrapper select {
            display: inline-block !IMPORTANT;
            vertical-align: top;
            margin: 0 2px 20px !IMPORTANT;
            width: auto !IMPORTANT;
            min-width: 60px;
            } ';
      return $css;
    }

	/**
	 * Code you want to run when all other plugins loaded.
	 */
	public function load_textdomain() {
		load_plugin_textdomain('cvct', false, basename(dirname(__FILE__)) . '/languages/' );
    }
    
    /*
|--------------------------------------------------------------------------
| fetches covid-19 all countries stats data
|--------------------------------------------------------------------------
*/ 
function cvct_get_all_country_data(){
    $cache_name='cvct_countries_data';
   // $cache=get_transient($cache_name);
   $cache=false;
    $country_stats_data = array();
    $data_arr = array();
      if($cache==false){
       $api_url = 'https://corona.lmao.ninja/countries?sort=cases';
       $api_req = wp_remote_get($api_url,array('timeout' => 120));
       if (is_wp_error($api_req)) {
        return false; // Bail early
    }
    $body = wp_remote_retrieve_body($api_req);
    $cs_data = json_decode($body);

     if(isset($cs_data)&& !empty($cs_data)){
    foreach($cs_data as  $all_country_data){
        $data_arr['country'] = $all_country_data->country;
        $data_arr['cases'] = $all_country_data->cases;
        $data_arr['active'] = $all_country_data->active;
        $data_arr['country'] =  $all_country_data->country;
        $data_arr['confirmed'] = $all_country_data->cases;
        $data_arr['recoverd'] = $all_country_data->recovered;
        $data_arr['deaths'] = $all_country_data->deaths;
       $country_stats_data[] = $data_arr;
      }
    set_transient($cache_name,
    $country_stats_data,
    CVCT_Cache_Timing);
   return $country_stats_data;
  }
 else{
     return false;
 }
  }
  else{
    return $country_stats_data =get_transient($cache_name);
  }
}
	/**
	 * Run when activate plugin.
	 */
	public function cvct_activate() {
		update_option("cvct-type","FREE");
		update_option("cvct_activation_time",date('Y-m-d h:i:s') );
		update_option("cvct-alreadyRated","no");
	}
	public function cvct_deactivate(){
		delete_transient('cvct_gs');
	}
}

function Corona_Virus_Cases_Tracker_lite() {
	return Corona_Virus_Cases_Tracker_lite::get_instance();
}

Corona_Virus_Cases_Tracker_lite();
