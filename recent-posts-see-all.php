<?php
/*
Plugin Name: Recent Posts See All
Plugin URI: http://smartestthemes.com/blog/recent-posts-widget-see-all-link/
Description: Adds a widget just like the regular Recent Posts, but with a link to See All Posts.
Version: 0.6.1
Author: Smartest Themes
Author URI: http://smartestthemes.com
License: GPL2
Text Domain: recent-posts-see-all
Domain Path: languages
*/

class Recent_Posts_See_All_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'widgets_init', array( $this, 'register_recent_posts_see_all' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}
	
	function register_recent_posts_see_all() {
		register_widget( 'Recent_Posts_See_All' );
	}

	function load_textdomain() {
		load_plugin_textdomain( 'recent-posts-see-all', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

$recent_posts_see_all = Recent_Posts_See_All_Plugin::get_instance();

/**
 * Adds Recent_Posts_See_All widget.
 */

class Recent_Posts_See_All extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'recent_posts_see_all', 'description' => __( 'Just like the Recent Posts widget, but with a link to See All Posts.', 'recent-posts-see-all' ) );
		parent::__construct('recent_posts_see_all', __('Recent Posts See All', 'recent-posts-see-all'), $widget_ops);

		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('widget_recent_posts_see_all', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
		$see_all_url = ( ! empty( $instance['see_all_url'] ) ) ? $instance['see_all_url'] : site_url('/blog/');
		$see_all_label = ( ! empty( $instance['see_all_label'] ) ) ? $instance['see_all_label'] : 'See All Posts';

		$r = new WP_Query( apply_filters( 'widget_posts_args', array( 'posts_per_page' => $number, 'no_found_rows' => true, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) ) );
		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<br />
		<p><a href="<?php echo $see_all_url; ?>" title="See All"><?php echo $see_all_label; ?></a></p>

		<?php echo $after_widget; ?>
		<?php wp_reset_postdata();
		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_recent_posts_see_all', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['see_all_url'] = strip_tags($new_instance['see_all_url']);
		$instance['see_all_label'] = strip_tags($new_instance['see_all_label']);
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['recent_posts_see_all']) )
			delete_option('recent_posts_see_all');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_recent_posts_see_all', 'widget');
	}

	function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$see_all_url = isset( $instance['see_all_url'] ) ? esc_attr( $instance['see_all_url'] ) : '';
		$see_all_label = isset( $instance['see_all_label'] ) ? esc_attr( $instance['see_all_label'] ) : '';
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'recent-posts-see-all' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:', 'recent-posts-see-all' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?', 'recent-posts-see-all' ); ?></label></p>

		<p><label for="<?php echo $this->get_field_id( 'see_all_url' ); ?>"><?php _e( '"See All" link URL, beginning with http', 'recent-posts-see-all' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'see_all_url' ); ?>" name="<?php echo $this->get_field_name( 'see_all_url' ); ?>" type="text" value="<?php echo $see_all_url; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'see_all_label' ); ?>"><?php _e( 'Replace "See All Posts" with your own text:', 'recent-posts-see-all' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'see_all_label' ); ?>" name="<?php echo $this->get_field_name( 'see_all_label' ); ?>" type="text" value="<?php echo $see_all_label; ?>" /></p>

<?php
	}
} // end class Recent_Posts_See_All