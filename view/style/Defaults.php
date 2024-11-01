<?php header("Content-type: text/css"); ?>

<?php
define('WP_USE_THEMES', false);

/** Loads the WordPress Environment and Template */
require('../../../../../wp-blog-header.php');

add_action('wp', 'no_headers');
function no_headers() {
	remove_all_actions('send_headers');
}

header("Content-type: text/css");
 echo <<<END
 /* == Set everything to defaults == */
#wgGlossaryItemList * { border:0px !important; text-decoration:none; padding:0 !important; margin:0 !important; }
#wgGlossaryItemList blockquote { margin:2em !important; }
#wgGlossaryItemList ul, #wgGlossaryItemList ol { margin-left:2em !important; }
#wgGlossaryItemList del { text-decoration: line-through !important; }
#wgGlossaryItemList ins { text-decoration: underline !important; color:#000 !important; background-color:#fff !important; }
#wgGlossaryItemList code { 
	display:block !important; border:3px !important; background-color:#000 !important; color:#00C700 !important; 
	opacity:.7; -moz-opacity:.7; -moz-border-radius: .5em; border-radius: .5em;
	margin: 2em 5em !important;
	padding: 1em !important;
}
END;
?>