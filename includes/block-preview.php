<?php
/**
 * ACF Block Preview Template.
 * Uses an iframe to preview the exact block UI from your decoupled frontend.
 *
 * The following variables are made available by WP/Gutenberg for use in this template
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during backend preview render.
 * @param   int $post_id The post ID the block is rendering content against.
 *          This is either the post ID currently being displayed inside a query loop,
 *          or the post ID of the post hosting this block.
 * @param   array $context The context provided to the block by the post or it's parent block.
 */

use CloakWP\API\BlockTransformer;
use CloakWP\CloakWP;
use CloakWP\Utils;

/* 
  If $block['data']['cloakwp_block_inserter_preview_image'] is set, it means we're rendering a preview image within
  the Gutenberg block inserter. Devs can set the image by adding this to block.json:
  {
    ...
    "example": {
      "attributes": {
        "mode": "preview",
        "data": {
          "cloakwp_block_inserter_preview_image": "/blocks/cards/screenshot.png"
        }
      }
    } 
  }
*/
if ( isset( $block['data']['cloakwp_block_inserter_preview_image'] ) ) :

  $image_path = $block['data']['cloakwp_block_inserter_preview_image'];

  // if $image_path starts with "/", we assume it's a relative path pointing to the image's location within the child theme; otherwise, we assume it's an absolute path/url
  if (strpos($image_path, '/') === 0) {
    $image_path = get_stylesheet_directory_uri() . $image_path;
  }

  echo '<img src="'. $image_path .'" style="width:100%; height:auto;">';

else : // handle regular Gutenberg Editor ACF Block iframe preview rendering:
  $is_block_inserter = $block['data']['cloakwp_block_inserter_iframe'];
  $first_render = 0;

  // Utils::write_log('=== iFrame Block Preview ===');
  // Utils::write_log(['$block: ', $block]);
  // Utils::write_log(['$content: ', $content]);
  // Utils::write_log(['$is_preview: ', $is_preview]);
  // Utils::write_log(['$post_id: ', $post_id]);
  // Utils::write_log(['$context: ', $context]);

  // Delete style.spacing because we don't want to render the spacing on the front-end preview because Gutenberg already adds the spacing within the editor
  unset($block['style']['spacing']);

  $data = $block['data'];
  $field_values = [];
  foreach ($data as $key => $value) {
    if (strpos($key, 'field_') === 0) {
      /* when previewing ACF Block where data has been updated, the $block value is very 
        different from when the data hasn't been updated -- the code below transforms the ACF data
        so that it's always in the same shape no matter whether the block's data has been changed 
        or not. This ensures previews after making changes don't break. 
      */
      $field_object = get_field_object($key);
      if ($field_object) {
        $field_name = $field_object['name'];
        $field_value = $field_object['value'];
        $field_values[$field_name] = $field_value;
        $field_values['_'.$field_name] = $key; // convertBlockToObject() requires this to work properly
      }
    } else {
      $first_render = 1;
    }
  }

  $formattedData = [
    'blockName' => $block['name'],
    'attrs' => [
      'align' => $block['align'],
      'style' => $block['style'],
      'backgroundColor' => $block['backgroundColor'],
      'data' => $first_render === 1 ? $data : $field_values,
    ]
  ];

  $blockTransformer = new BlockTransformer();
  $blockData = $blockTransformer->convertBlockToObject($formattedData, $post_id);
  $json = json_encode($blockData);
  $urlEncodedJson = urlencode($json);
  
  $CloakWP = CloakWP::getInstance();
  $frontend = $CloakWP->getActiveFrontend();
  $frontendUrl = $frontend->getUrl();
  $settings = $frontend->getSettings();
  $iframeUrl = "$frontendUrl/{$settings['blockPreviewPath']}?secret={$settings['authSecret']}&blockData=$urlEncodedJson";
    
  ?>
  <div class="cloakwp-block-preview-ctnr">
    <!-- Block selector icon overlay on hover -->
    <?php if(!$is_block_inserter) : ?>
      <div class="cloakwp-block-selector">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="cloakwp-block-selector-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
    <?php endif; ?>
    <iframe
      class="block-preview-iframe <?php echo $is_block_inserter ? 'in-block-inserter' : '' ?>"
      src='<?php echo $iframeUrl ?>' 
      title="Block Preview"
      width="100%"
      scrolling="no"
      allow-same-origin
    ></iframe>
  </div>
<?php
endif;

