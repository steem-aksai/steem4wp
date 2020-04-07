<?php
/*
 * WordPress Custom API Data Hooks
 */

if ( !defined( 'ABSPATH' ) ) exit;

add_filter( 'user_contactmethods',function($userInfo) {
  $userInfo['steemId']         = __( 'Steem ID' );
  return $userInfo;
});

// add Steem options for publishing post in user profile page
add_action( 'personal_options_update', 'update_steem_user_settings' );
add_action( 'edit_user_profile_update', 'update_steem_user_settings' );

function update_steem_user_settings( $user_id ) {
  if ( !current_user_can( 'edit_user', $user_id ) )
    return false;
  update_user_meta( $user_id, 'user_steem_id', $_POST['user_steem_id'] );
  update_user_meta( $user_id, 'user_steem_posting_key', $_POST['user_steem_posting_key'] );
  update_user_meta( $user_id, 'user_steem_default_tags', $_POST['user_steem_default_tags'] );
  update_user_meta( $user_id, 'user_steem_footer', $_POST['user_steem_footer'] );
}

add_action( 'show_user_profile', 'get_steem_user_settings' );
add_action( 'edit_user_profile', 'get_steem_user_settings' );

function get_username($user) {
  $username = get_the_author_meta( 'user_steem_id', $user->ID );
  if (empty($username) && !empty($user->steemId)) {
    $username = $user->steemId;
  }
  return $username;
}

function get_default_footer($user) {
  $default_footer = get_the_author_meta( 'user_steem_footer', $user->ID );
  if (empty($default_footer)) {
    $default_footer = "<br /><center><hr/><em>本文使用 <a href='https://github.com/steem-aksai/steem4wp'>Steem4WP</a> 发布；原文来自 : [%original_link%] </em><hr/></center>";
  }
  return $default_footer;
}

function get_steem_user_settings( $user ) { ?>
<h2>Steem for Wordpress</h2>
<table class="form-table">
    <tr>
        <th><label for="user_steem_id">Steem ID</label></th>
        <td>
            <input type="text" name="user_steem_id" id="user_steem_id" class="regular-text" value="<?php echo htmlspecialchars(get_username($user), ENT_QUOTES); ?>"/>
        </td>
    </tr>
    <tr>
        <th><label for="user_steem_posting_key">发帖秘钥</label></th>
        <td>
            <input type="password" name="user_steem_posting_key" id="user_steem_posting_key" class="regular-text" value="<?php echo htmlspecialchars(get_the_author_meta( 'user_steem_posting_key', $user->ID ), ENT_QUOTES); ?>"/>
        </td>
    </tr>
    <tr>
        <th><label for="user_steem_default_tags">默认标签</label></th>
        <td>
            <input type="text" name="user_steem_default_tags" id="user_steem_default_tags" class="regular-text" value="<?php echo htmlspecialchars(get_the_author_meta( 'user_steem_default_tags', $user->ID ), ENT_QUOTES); ?>"/>
        </td>
    </tr>
    <tr>
        <th><label for="user_steem_footer">文章脚注</label></th>
        <td>
          <textarea name="user_steem_footer" id="user_steem_footer" rows="5" columns="30"> <?php echo htmlspecialchars(get_default_footer($user), ENT_QUOTES); ?> </textarea>
        </td>
    </tr>
</table>
<?php }

// show Steem options in publish post page
add_action('add_meta_boxes', 'add_steem_custom_box');
function add_steem_custom_box() {
    $post_id = get_the_ID();
    if (get_post_type($post_id) != 'post') {
        return;
    }
    add_meta_box(
      'steem_custom_box_id',
      'Steem',
      'display_steem_custom_box',
      'post',
      'side'
    );
}
function display_steem_custom_box($post){
    $author_id = $post->post_author;
    $post_id = get_the_ID();

    // Before Publish
    if (get_post_status($post_id) != 'publish') {
        $value = get_post_meta($post_id, 'user_steem_publish_post', true);
        if ($value == "0") {
            $checked = "";
        } else {
            $checked = "checked";
        }
        wp_nonce_field('steem_custom_nonce_'.$post_id, 'steem_custom_nonce');
        $body  = '<label><input type="checkbox" value="1" '.$checked.' name="user_steem_publish_post" /> <input type="hidden" name="user_steem_not_publish_post" value="0" />发布到Steem</label>';
        echo $body;
    }
}

// add hook for publishing to Steem
add_action( 'transition_post_status', 'steem_publish_post', 15, 3 );
function steem_publish_post($new_status, $old_status, $post) {
    // If post is empty
    if (empty($_POST)) {
        return;
    }

    if ($new_status == 'publish' &&  $old_status == 'publish' && $post->post_type == 'post') {
      // new post or edit post
      if (!isset($_POST['user_steem_publish_post']) && isset($_POST['user_steem_not_publish_post']) ) {
        return ;
      } else {
        $posting_key = get_user_meta(get_current_user_id(), 'user_steem_posting_key', true);
        $username = get_user_meta(get_current_user_id(), 'user_steem_id', true);
        if (!empty($posting_key) && !empty($username)) {
          $tags = wp_get_post_tags($post->ID);
          if (empty($tags)) {
            $tags = get_user_meta(get_current_user_id(), 'user_steem_default_tags', true );
            if (!empty($tags) && is_string($tags)) {
              $tags = strtolower($tags);
              $tags = wp_parse_list($tags);
            }
          } else {
            $tags_arr = [];
            foreach ($tags as $t) {
                $tags_arr []= str_replace(" ", "", $t->name);
            }
            $tags = $tags_arr;
          }
          $footer = get_user_meta(get_current_user_id(), 'user_steem_footer', true );
          if (!empty($footer)) {
            $footer = str_replace("[%original_link%]", get_permalink( $post->ID ), $footer);
          }
          write_log("steem4wp: public post by @{$username}");
          write_log($tags);
          write_log("------");
          $steem_ops = new WP_Steem_Ops();
          $steem_ops->create_post($username, $post->ID, $tags, $footer);
        }
        return ;
      }
    }
    // else if ($new_status == 'publish' &&  $old_status != 'publish' && $post->post_type == 'post') {
    //   // edit post
    //   if (!isset($_POST['user_steem_publish_post']) && isset($_POST['user_steem_not_publish_post']) ) {
    //   } else {
    //     // $username = get_user_meta( get_current_user_id(), 'user_steem_id', true );
    //     // $steem_ops = new WP_Steem_Ops();
    //     // $steem_ops->update_post($username, $post);
    //   }
    // }

    return;
}
