<?php
/*
	wordgallery-glossary/model/functions.php
*/

// Shortcode to display glossary anywhere:
function wgGlossary_glossary_terms_shortcode( $attr ) {
	extract( shortcode_atts( array(
		'style' => wgGlossaryDisplayStyle, 
		'groups' => null, 
		'not_groups' => null, 
		'use_jquery' => wgGlossaryUseJQuery, 
		'keep_open_number' => wgGlossaryJQueryFirstOpen, 
		'group_organize' => wgGlossaryUseGroupsTaxonomy, 
		'show_group_names' => wgGlossaryShowGroupTitles, 
		'show_group_key' => wgGlossaryShowGroupKey, 
		'show_read_more' => wgGlossaryShowReadMoreLink, 
		'read_more_text' => wgGlossaryReadMoreText, 
		'ignore_excerpts' => wgGlossaryIgnoreExcerpts, 
		'show_credit_link' => wgGlossaryShowCreditLink
	), $attr ) );
	
	return wgGlossary_display_glossary(FALSE, $style, $groups, $not_groups, $group_organize, $show_group_names, $show_group_key, $use_jquery, $read_more_text, $ignore_excerpts);

}

// Display glossary on page selected in settings:
function wgGlossary_glossary_terms_page_override($content) {
	$content .= wgGlossary_display_glossary();
	return $content;
}

// Display glossary terms:
function wgGlossary_display_glossary(
	$overridePageVersion = TRUE, 
	$displayStyle = wgGlossaryDisplayStyle, 
	$groupsFilter = null, 
	$notGroupsFilter = null, 
	$useGroupsOrganization = wgGlossaryUseGroupsTaxonomy, 
	$showGroupNames = wgGlossaryShowGroupTitles, 
	$showGroupNameIndex = wgGlossaryShowGroupKey, 
	$useJQuery = wgGlossaryUseJQuery, 
	$readMoreText = wgGlossaryReadMoreText,
	$ignoreExcerpts = wgGlossaryIgnoreExcerpts) {
	
	$glossaryDisplayOrder = 'ASC';
	
	if (!(($overridePageVersion === FALSE) || ($overridePageVersion === TRUE))) {
		exit(__('ERROR: First argument of wgGlossary_display_glossary() must be of type: Boolean.'));
	}
	
	if (empty($content)) {
		$content = '';
	}
	
	$jQueryOption = $useJQuery ? "wggjQuery" : "wgg_noJQuery";

	
	if (($overridePageVersion === FALSE) || ($overridePageVersion && is_numeric(wgGlossaryPageToOverride) && is_page(wgGlossaryPageToOverride))){
		// First filter the groups accordingly if necessary:
		if ($groupsFilter && $notGroupsFilter){
			// This doesn't really work, only the $groupsFilter will be used by get_terms.
			$groups = get_terms(wgGlossaryCustomTaxonomySlug, "include=$groupsFilter&exclude=$notGroupsFilter");
		} elseif ($groupsFilter) {
			// Include only groups passed by $groupsFilter.
			$groups = get_terms(wgGlossaryCustomTaxonomySlug, "include=$groupsFilter");
		} elseif ($notGroupsFilter) {
			// Exclude any groups passed by $notGroupsFilter.
			$groups = get_terms(wgGlossaryCustomTaxonomySlug, "exclude=$notGroupsFilter");
		} else {
			// Return all groups in taxonomy.
			$groups = get_terms(wgGlossaryCustomTaxonomySlug);
		}
		if ($useGroupsOrganization) { // Use the custom taxonomy Group to organize our glossary terms:
			if ( !empty($groups) ) {
				if ($showGroupNameIndex) {
					// Show a list of links to each group title at the top of the term list.
					$content .= '<div id="groupNameIndex">';
					$count = count($groups);
					$i = 1;
					foreach ($groups as $group) {
						$content .= '<a href="#'.$group->name.'">'.$group->name.'</a>';
						$content .= ($i < $count) ? " | " : "";
						$i++;
					}
					$content .= '</div>';
				}
				$content .= '<div id="wgGlossaryItemList" class="' . $jQueryOption . ' ' . $displayStyle . '">';
			  foreach ($groups  as $group ) {
			  	if (!empty($group->name)) {
				  	$content .= '<div class="wgGlossaryItemGroup">';
				  	$content .= '<a class="wgGlossaryItemGroupTarget" name="'.$group->name.'"></a>';
				  	if ($showGroupNames) {
					  	$content .= '<div class="wgGlossaryGroupName">' . $group->name . '</div>';
				  	}
						$glossary_item_index = get_posts(array(
															'post_type'		=> wgGlossaryCustomPostTypeSlug,
															'post_status'	=> 'publish',
															'orderby'		=> 'title',
															'order'			=> $glossaryDisplayOrder,
															'post_parent'	=> null,
															wgGlossaryCustomTaxonomySlug => $group->slug,
															));
						if ($glossary_item_index) {
							foreach ($glossary_item_index as $item) {
								$content .= '<div class="wgGlossaryItemWrapper"><h4 class="wgGlossaryItemTitle"><a href="' . get_permalink($item) . '">' . $item->post_title . '</a></h4>';
								$content .= '<div class="wgGlossaryItemDefinition">';
								if ((($item->post_excerpt == "") || ($ignoreExcerpts) == 1)) {
									$content .= $item->post_content;
								} else {
									$content .= $item->post_excerpt;
								}
								if (get_option('wgGlossary_show_read_more_link') == 1) { 	
									$readMoreLink = ' <br/> <a class="wgGlossaryItemReadMoreLink" style="float:right;" href="' . get_permalink($item) . '">' . $readMoreText .'</a>';
									$content .= $readMoreLink;
								}
									$content .= "</div></div>";
							}
							$content .= "</div>";
						}
			  	}
			  }
				if (get_option('wgGlossary_show_credit_link')) {
					$content .= '<div class="wgGlossaryCreditLink" style="text-align:center; margin:2em !important; clear:both;">Glossary created using <a href="http://wordgallery-glossary.allstruck.com">WordGallery Glossary</a></div>';
				}
				$content .= '</div>';
			}  

		} else { // Option to use groups is off, just show all of the terms together (or filtered if using shortcode):
			if ($groupsFilter || $notGroupsFilter) {
				$theseGroups = array();
				foreach ($groups as $group) {
					array_push($theseGroups, $group->term_id);
				}
				$args = array(
					'post_type' => wgGlossaryCustomPostTypeSlug, 
					'post_status' => 'publish', 
					'orderby' => 'title', 
					'order' => $glossaryDisplayOrder,
					'tax_query' => array(
						array(
							'taxonomy' => wgGlossaryCustomTaxonomySlug,
							'field' => 'id',
							'terms' => $theseGroups
						)
					)
				);
				$glossary_item_index = get_posts($args);
			} else {
				$glossary_item_index = get_children(array(
													'post_type'		=> wgGlossaryCustomPostTypeSlug,
													'post_status'	=> 'publish',
													'orderby'		=> 'title',
													'order'			=> $glossaryDisplayOrder,
													'post_parent'	=> null,
													));
			}
			if ($glossary_item_index){	
				$content .= '<div id="wgGlossaryItemList" class="' . $jQueryOption . ' ' . $displayStyle . '">';
				foreach($glossary_item_index as $item){
					$content .= '<div class="wgGlossaryItemWrapper"><h4 class="wgGlossaryItemTitle"><a href="' . get_permalink($item) . '">' . $item->post_title . '</a></h4>';
					$content .= '<div class="wgGlossaryItemDefinition">';
					if ((($item->post_excerpt == "") || ($ignoreExcerpts) == 1)) {
						$content .= $item->post_content;
					} else {
						$content .= $item->post_excerpt;
					}
					if (get_option('wgGlossary_show_read_more_link') == 1) { 	
						$readMoreLink = ' <br/> <a class="wgGlossaryItemReadMoreLink" style="float:right;" href="' . get_permalink($item) . '">' . $readMoreText .'</a>';
						$content .= $readMoreLink;
					}
						$content .= "</div></div>";
				}
				if (get_option('wgGlossary_show_credit_link')) {
					$content .= '<div class="wgGlossaryCreditLink" style="text-align:center; margin:2em !important; clear:both;">Glossary created using <a href="http://wordgallery-glossary.allstruck.com">WordGallery Glossary</a></div>';
				}
				$content .= '</div>';
			}
		}
	}
	return $content;
}


function load_language() {
	// Internationalization, load all languages in the language folder.
	load_plugin_textdomain('wordgallery-glossary', false, basename( dirname( __FILE__ ) ) . '/language');
}

function admin_notices() {
	// Alert if display style is not set
	if (!ereg('.css$', get_option("wgGlossary_display_style")) && !ereg('.php$', get_option("wgGlossary_display_style"))) {
		echo '<div class="error"><p><strong>';
			_e('The WordGallery Glossary plugin is active but you need to select a style on the ');
		echo '<a href="'. admin_url() . 'options-general.php?page=' . wgGlossarySettingsPageSlug . '">';
			_e('WG Glossary options page');
		echo '</a>.</strong></p></div>';
	}
	// Alert if override page is not set
	if (get_option("wgGlossary_page_to_override") == "") {
		echo '<div class="error"><p><strong>';
			_e('The WordGallery Glossary plugin is active but you do not have a page selected.');
		echo '</p>';
		echo '<p>';
			_e('Create and publish a blank page and select it on the ');
		echo '<a href="'. admin_url() . 'options-general.php?page=' . wgGlossarySettingsPageSlug .'">';
			_e('options page');
		echo '</a>.</strong></p></div>';
	}
}



/**
 * Add a link to the settings page on the plugins list
 */
function wg_add_action_link( $links, $file ) {
	static $this_plugin;
	$this_plugin = 'wordgallery-glossary/wordgallery-glossary.php';
	$plugin_options_url = '/wp-admin/options-general.php?page=' . wgGlossarySettingsPageSlug;

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="' . $plugin_options_url . '">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

/* Add jQuery include using enqueue_script */
function wgg_add_jquery_to_frontend() {
		$jQueryOptionEnabled = get_option("wgGlossary_use_jQuery");
		$glossaryPageID = get_option("wgGlossary_page_to_override");
		
		$currentPageHasShortcode = TRUE;
		
		# Enqeue the jQuery only if the visitor is viewing the glossary page (or one using the shortcode) right now:
		if ((is_page($glossaryPageID) && $jQueryOptionEnabled) || $currentPageHasShortcode) {
			wp_deregister_script('jquery');
			wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js');
			wp_enqueue_script('jquery');
		}
}

/* Add Stylesheets to frontend */
function wgg_add_stylesheets_to_frontend() {
	// Add default style to header:
	wp_register_style('wgGlossaryStyleDefaults', WP_PLUGIN_URL . '/wordgallery-glossary/view/style/Defaults.php');
	wp_enqueue_style('wgGlossaryStyle');
	
	// Pull up all available styles from style directory:
	$styleSheets = array();
	if ($handle = opendir(WP_PLUGIN_DIR . '/wordgallery-glossary/view/style')) {
    while (false !== ($file = readdir($handle))) {
			if (ereg('.css$', $file)) {
				array_push($styleSheets, $file);
			}
    }
    closedir($handle);
	}
	
	// Add all available styles to header:
	foreach ($styleSheets as $styleSheet) {
		wp_register_style( $styleSheet, WP_PLUGIN_URL . '/wordgallery-glossary/view/style/' . $styleSheet );
		wp_enqueue_style($styleSheet);
	}
}




function wgg_jquery_folding_script() {
				extract($GLOBALS);
				$pluginURL = $wp_plugin_url;
				
				global $content;
				$content .= '
	<script type="text/javascript">
	/* WordGallery Glossary jQuery animated folding script */
	/* <![CDATA[ */
		jQuery.noConflict();
		(function($) {
			function initialGlossaryItemAnimation() {';
									if (get_option('wgGlossary_jQuery_first_open') > 0) {			
						$content .=	'
					$("#wgGlossaryItemList.wggjQuery .wgGlossaryItemWrapper h4").eq('. (get_option("wgGlossary_jQuery_first_open") - 1) .').addClass("active");
					$("#wgGlossaryItemList.wggjQuery .wgGlossaryItemWrapper h4.active").next("div.wgGlossaryItemDefinition").slideToggle();
					$("#wgGlossaryItemList.wggjQuery .wgGlossaryItemWrapper h4").next("div.wgGlossaryItemDefinition").slideToggle();
					';
								} else {
						$content .=	'
					$("#wgGlossaryItemList.wggjQuery .wgGlossaryItemWrapper h4").next("div.wgGlossaryItemDefinition").slideToggle();';
								}
					$content .= '
			};';
			$content .= '
			$(function() {';
		
					$content .=	'
							$("body").ajaxComplete(function() { initialGlossaryItemAnimation(); });

				initialGlossaryItemAnimation();
					
				$("#wgGlossaryItemList.wggjQuery .wgGlossaryItemWrapper h4").live("click", function() {
					if ($(this).hasClass("active")) {
						$(this).next("div.wgGlossaryItemDefinition:visible").slideUp("fast");
						$(this).toggleClass("active");
						$(this).removeClass("active");
					} else {
						$(this).next("div.wgGlossaryItemDefinition").slideToggle("slow");
						$("h4.active").next("div.wgGlossaryItemDefinition:visible").slideUp("fast");
						$("h4.active").removeClass("active");
						$(this).toggleClass("active");
						$(this).siblings("h4").removeClass("active");
					}
				return false;
				});
			});
		})(jQuery);
	/* ]]> */
	</script>
';	echo $content;
}




function wgGlossary_activate_plugin() {
	wgGlossary_create_options();
	flush_rewrite_rules();
}

function wgGlossary_deactivate_plugin() {
}

?>