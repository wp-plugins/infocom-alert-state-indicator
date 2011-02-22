<?php
/*
Plugin Name: Infocon Alert State Indicator
Description: Displays the infocon state as indicated by the ISC as an image, link or as colored text. Note: 'IMG' will display a GIF sized 354x92, suitable for footers or wide sidebars, 'SMALLIMG' scales the same image down to 177x48 px. 
Version: 1.00
Author: ax11
Author URI: http://www.ax11.de
License: GPL

    Copyright 2011  Thomas Amm  (email : incoming.lists@ax11.de

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ISCAlertSC extends WP_Widget
{
	
	/** Defs (Non-WP) 
	* @TODO: i18n, ugly workaround follows 
	 */
	   public $lang='en';  
       public $IscState='';
       public $IscColor='';
       public $statEN=array(
		'green',
		'yellow',
		'orange',
		'red'
	);
	   

	public $statPO=array();
	// URI for plaintext alert
	const TXTURI = 'http://isc.sans.edu/infocon.txt'; 	
	// URI for GIF image
	const IMGURI = 'http://isc.sans.edu/images/status.gif'; 
	// ...HTML (green|yellow|orange|red) + headline
	const HTMLURI= 'http://isc.sans.edu/daily_alert.html'; 
	
	/** constructor and overwritten parent methods 
	 *  http://codex.wordpress.org/Widgets_API
	 */
    function ISCAlertSC() {
        parent::WP_Widget(false, $name = 'ISCAlertSC');	
     
    } 

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title;
                        /** 
                        * 
                        * <<< Here! Me! Me! >>> 
                        *
                        */
              $this->PrintAlert();   
              $this->PrintState($instance['format']);
               echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['format'] = strip_tags($new_instance['format']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
    	$defaults = array( 'title' => 'Infocom Status', 'format' => 'TXT' );
		$instance = wp_parse_args( (array) $instance, $defaults ); 
        $title = esc_attr($instance['title']);
        $format = esc_attr($instance['format']);
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />

        </p>
        <p>
			<label for="<?php echo $this->get_field_id( 'format' ); ?>">Format:</label>
			<select id="<?php echo $this->get_field_id( 'format' ); ?>" name="<?php echo $this->get_field_name( 'format' ); ?>" class="widefat" style="width:100%;">
			<option <?php if ( 'HTML' == $instance['format'] ) 
				echo 'selected="selected"'; ?>>HTML</option>
				<option <?php if ( 'TXT' == $instance['format'] )echo 'selected="selected"'; ?>>TXT</option>
				<option <?php if ( 'SMALLIMG' == $instance['format'] )echo 'selected="selected"'; ?>>SMALLIMG</option>
				<option <?php if ( 'IMG' == $instance['format'] ) echo 'selected="selected"'; ?>>IMG</option>
			</select>
			</p>
			<ul style="list-style:disc inside;">
				<li><em>TXT:</em> <?php _e("simple ASCII text");?></li>
				<li><em>HTML:</em> <?php _e("color, link to isc.sans.org");?></li>
				<li><em>SMALLIMG:</em> <?php _e("small image, linked to isc.sans.org");?></li>
				<li><em>IMG:</em> <?php _e("regular image, linked to isc.sans.org");?></li>
			</ul>
			
        <?php 
    }
	/** ISCAlertSC::SetColor Query ISC URI for alert state */
	private function SetColor()
	{ 
		$handle=@fopen(self::TXTURI,r);
		if (!$handle){ 
			echo "<b>HANDLE!</b>";
			return FALSE;
		}
		$this->IscColor=fgets($handle);
		return TRUE;
		}
		
	/** ISCAlertSC::SetState: check state against list of legal states to
	 *  prevent code injection,
	 *	@param string ISCAlertSC::IscColor value from fopen(URI)	
	 */
	 
	private function SetState(){	
		//echo $this->statPO[$this->IscColor];
		if (!is_string($this->IscColor)) return(FALSE);
		if (in_array($this->IscColor,$this->statEN))
		{   //english state is key now
			// $this->stat=array_flip($this->stat);
			// $i=$this->stat[$this->IscColor]; 
			//get value "color" for index i from l18ed-array
			// $this->IscState=$this->statPO[$i];
			$this->IscState=__($this->IscColor,'isc-alert-widget');
			return TRUE;
		}
		return FALSE;
	}

	
    /** 
	*	ISCAlertSC::ISCAlertSC 
	*	Define some (mostly still unused) arrays, try to open URI of ISC's text-only status service
	*	exit smoothly at fail.
	*	@todo: directory tree, svn
	*/
	public function PrintAlert()
	{ 
	//Localization
		if (defined('WPLANG') && function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('isc-alert-widget', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/lang');
		} 
		if(!$this->SetColor())	die('No COlor!'); // this should never happen!
		if(!$this->SetState()) return(FALSE);		
	}
	
	/**
	 * @function PrintState($format) output formatted state (the actual FILTER)
	 * @param string $format enum('HTML','TXT','IMG','SMALLIMG')
	 * @todo join w. ISCPrintAlertImage($size=''
	 */
	public function PrintState($format)
	{
		if($format=='TXT'){
			echo $this->IscState;
			return;
		}
		if($format=='HTML'){
			echo '<a href="http://isc.sans.org/"  style="color:'.$this->IscColor.'">'.$this->IscState.'</a>';
			return;
		}
		if($format=='SMALLIMG'){
			$sizestring=' width="177" height="46"';
			}
		else //if($format=='IMG')
		{
			$sizestring=' width="354" height="92"';
		}
		echo '<a href="http://isc.sans.org/"><img src="'.self::IMGURI.'"'.$sizestring.' alt="ISC Infocon Status:"'.$this->IscState.'"/></a>';
	}
	
} // class ISCAlertSC

add_action('widgets_init', create_function('', 'return register_widget("ISCAlertSC");'));

?>
