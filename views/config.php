<div class="wrap">

  <h2><?php esc_html_e( 'Simple drizzle' , 'drizzle');?></h2>

  <div class="have-key">
    <div id="wpcom-stats-meta-box-container" class="metabox-holder">
      <div class="postbox-container" style="width: 75%">
        <div id="referrers" class="postbox ">
          <h3 class="hndle"><span><?php esc_html_e( 'Settings' , 'drizzle');?></span></h3>
          <p style="margin-left: 20px">1. Register at <a href="https://app.getdrizzle.com/signup" target="_blank">Drizzle</a>.</p>
          <p style="margin-left: 20px">2. Generate API key at "Setup" tab. Copy API key. Paste API key below. Save.</p>
          <form name="drizzle_conf" id="drizzle-conf" action="<?php echo esc_url( Drizzle_Admin::get_page_url() ); ?>" method="POST">
            <div class="inside">
              <table cellspacing="0" class="drizzle-settings">
                <tbody>
                  <tr>
                    <th class="drizzle-api-key" width="10%" align="left" scope="row"><?php esc_html_e('API Key', 'drizzle');?></th>
                    <td width="5%"/>
                    <td align="left">
                      <span class="api-key">
                        <input id="key" name="key" type="text" size="15" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text code">
                      </span>
                      <?php if ( $api_key ) : ?>
                      <?php if ( $is_key_valid ) : ?>
                        <span style="color: green; margin-left: 10px;">Connected to Drizzle</span>
                      <?php else : ?>
                        <span style="color: red; margin-left: 10px;">API Key is invalid!</span>
                      <?php endif;endif; ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div id="major-publishing-actions">
              <?php if ( $api_key ) : ?>
                <div id="delete-action">
                  <a class="submitdelete deletion" href="<?php echo esc_url( Drizzle_Admin::get_page_url( 'delete_key' ) ); ?>"><?php esc_html_e('Disconnect from Drizzle', 'drizzle'); ?></a>
                </div>
              <?php endif; ?>
              <?php wp_nonce_field(Drizzle_Admin::NONCE) ?>
              <div id="publishing-action">
                <input type="hidden" name="action" value="enter-key">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'drizzle');?>">
              </div>
              <div class="clear"></div>
            </div>
          </form>
        </div>
        <div style="text-align: center">
          <iframe src="https://player.vimeo.com/video/182750960" width="711" height="400" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
</div>
