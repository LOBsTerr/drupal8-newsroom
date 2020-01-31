<?php

namespace Drupal\newsroom_connector_item\Plugin\newsroom;

use Drupal\newsroom_connector\Plugin\NewsroomProcessorBase;

/**
 * Handles typical operations for newsroom item.
 *
 * @NewsroomProcessor (
 *   id = "newsroom_item",
 *   content_type = "node",
 *   bundle = "newsroom_item",
 *   bundle_field = "type",
 *   import_script = "fullrss-multilingual.cfm",
 *   import_segment = "item_id",
 *   label = @Translation("Newsroom item")
 * )
 */
class NewsroomItemNewsroomProcessor extends NewsroomProcessorBase {

}
