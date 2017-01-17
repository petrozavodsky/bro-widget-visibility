<?php

/*
Plugin Name: Bro Widget Visibility
Author: Petrozavodsky
Author URI: https://alkoweb.ru
*/


class BroWidgetVisibility {
	private static $version = '1.0.0';
	private static $textdomain = 'bro_widget_visibility';
	private static $file;
	public static $count_terms = 1000;

	public static function init( $file ) {
		self::$file = $file;
		if ( is_admin() ) {
			add_action( 'sidebar_admin_setup', array( __CLASS__, 'widget_admin_setup' ) );
			add_filter( 'widget_update_callback', array( __CLASS__, 'widget_update' ), 10, 3 );
			add_action( 'in_widget_form', array( __CLASS__, 'widget_conditions_admin' ), 10, 3 );
			add_action( 'wp_ajax_widget_conditions_options', array( __CLASS__, 'widget_conditions_options' ) );
		} else {
			add_action( 'widget_display_callback', array( __CLASS__, 'filter_widget' ) );
			add_action( 'sidebars_widgets', array( __CLASS__, 'sidebars_widgets' ) );
		}

//		self::widget_conditions_options_taxonomies( 'collection', 'collection' );
	}

	public static function widget_admin_setup() {
		wp_enqueue_style( 'widget-conditions', plugins_url( 'public/admin/css/widget-conditions.css', self::$file ) );
		wp_enqueue_script( 'widget-conditions', plugins_url( 'public/admin/js/widget-conditions.js', self::$file ), array(
			'jquery',
			'jquery-ui-core'
		), self::$version, true );
	}

	public static function widget_conditions_options_echo( $major = '', $minor = '' ) {
		switch ( $major ) {
			case 'category':
				?>
                <option value="all">
					<?php _e( 'All category pages', self::$textdomain ); ?>
                </option>
				<?php
				$categories = get_categories( array(
					'number'  => self::$count_terms,
					'orderby' => 'count',
					'order'   => 'DESC'
				) );
				usort( $categories, array( __CLASS__, 'strcasecmp_name' ) );
				foreach ( $categories as $category ) {
					?>
                    <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $category->term_id, $minor ); ?>>
						<?php echo esc_html( $category->name ); ?>
                    </option>
					<?php
				}
				break;
			case 'author':
				?>
                <option value="">
					<?php _e( 'All author pages', self::$textdomain ); ?>
                </option>
				<?php

				foreach ( get_users( array( 'orderby' => 'name', 'exclude_admin' => true ) ) as $author ) {
					?>
                    <option
                            value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $author->ID, $minor ); ?>>
						<?php echo esc_html( $author->display_name ); ?>
                    </option>
					<?php
				}
				break;
			case 'tag':
				?>
                <option value="all">
					<?php _e( 'All tag pages', self::$textdomain ); ?>
                </option>
				<?php

				$tags = get_tags( array(
					'number'     => self::$count_terms,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'hide_empty' => false
				) );
				usort( $tags, array( __CLASS__, 'strcasecmp_name' ) );

				foreach ( $tags as $tag ) {
					?>
                    <option value="<?php echo esc_attr( $tag->term_id ); ?>" <?php selected( $tag->term_id, $minor ); ?>>
						<?php echo esc_html( $tag->name ); ?>
                    </option>
					<?php
				}
				break;
			case 'date':
				?>
                <option value="" <?php selected( '', $minor ); ?>>
					<?php _e( 'All date archives', self::$textdomain ); ?>
                </option>
                <option value="day"<?php selected( 'day', $minor ); ?>>
					<?php _e( 'Daily archives', self::$textdomain ); ?>

                </option>
                <option value="month"<?php selected( 'month', $minor ); ?>>
					<?php _e( 'Monthly archives', self::$textdomain ); ?>
                </option>
                <option value="year"<?php selected( 'year', $minor ); ?>>
					<?php _e( 'Yearly archives', self::$textdomain ); ?>

                </option>
				<?php
				break;
			case 'page':
				if ( ! $minor ) {
					$minor = 'post_type-page';
				} else if ( 'post' == $minor ) {
					$minor = 'post_type-post';
				}

				?>
                <option value="front" <?php selected( 'front', $minor ); ?>>
					<?php _e( 'Front page', self::$textdomain ); ?>
                </option>
                <option value="posts" <?php selected( 'posts', $minor ); ?>>
					<?php _e( 'Posts page', self::$textdomain ); ?>
                </option>
                <option value="404" <?php selected( '404', $minor ); ?>>

					<?php _e( '404 error page', self::$textdomain ); ?>
                </option>
                <option value="search" <?php selected( 'search', $minor ); ?>>
					<?php _e( 'Search results', self::$textdomain ); ?>

                </option>
                <optgroup label="<?php esc_attr_e( 'Post type:', self::$textdomain ); ?>">
					<?php

					$post_types = get_post_types( array( 'public' => true ), 'objects' );

					foreach ( $post_types as $post_type ) {
						?>
                        <option value="<?php echo esc_attr( 'post_type-' . $post_type->name ); ?>" <?php selected( 'post_type-' . $post_type->name, $minor ); ?>>
							<?php echo esc_html( $post_type->labels->singular_name ); ?>
                        </option>
						<?php
					}
					?>
                </optgroup>
                <optgroup label="<?php esc_attr_e( 'Static page:', self::$textdomain ); ?>">
					<?php
					echo str_replace( ' value="' . esc_attr( $minor ) . '"', ' value="' . esc_attr( $minor ) . '" selected="selected"', preg_replace( '/<\/?select[^>]*?>/i', '', wp_dropdown_pages( array( 'echo' => false ) ) ) );
					?>
                </optgroup>
				<?php
				break;
		}
		self::widget_conditions_options_taxonomies( $major, $minor );
	}

	public static function widget_conditions_options_taxonomies( $major, $minor ) {
		$taxonomies = get_taxonomies( array(
			'public'   => true,
			'_builtin' => false
		) );

		if ( array_key_exists( $major, $taxonomies ) ) :

			foreach ( $taxonomies as $taxonomy ):
				$taxonomy_obj = get_taxonomy( $taxonomy );
				?>
                <option value="all">
					<?php _e( 'All ' ); ?>
					<?php echo $taxonomy_obj->label; ?>
					<?php _e( ' pages', self::$textdomain ); ?>
                </option>
				<?php
				$args  = array(
					'number'     => self::$count_terms,
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				);
				$terms = get_terms( $args );

				foreach ( $terms as $term ) :?>
                    <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $minor ); ?>>
						<?php echo esc_html( $term->name ); ?>
                    </option>
					<?php
				endforeach;

			endforeach;
		endif;
	}

	/**
	 * This is the AJAX endpoint for the second level of conditions.
	 */
	public static function widget_conditions_options() {
		self::widget_conditions_options_echo( $_REQUEST['major'], isset( $_REQUEST['minor'] ) ? $_REQUEST['minor'] : '' );
		die;
	}

	/**
	 * Add the widget conditions to each widget in the admin.
	 *
	 * @param $widget unused.
	 * @param $return unused.
	 * @param array $instance The widget settings.
	 */
	public static function widget_conditions_admin( $widget, $return, $instance ) {
		$conditions = array();

		if ( isset( $instance['conditions'] ) ) {
			$conditions = $instance['conditions'];
		}

		if ( ! isset( $conditions['action'] ) ) {
			$conditions['action'] = 'show';
		}

		if ( empty( $conditions['rules'] ) ) {
			$conditions['rules'][] = array( 'major' => '', 'minor' => '' );
		}

		?>
        <div class="widget-conditional <?php if ( empty( $_POST['widget-conditions-visible'] ) || $_POST['widget-conditions-visible'] == '0' ) { ?>widget-conditional-hide<?php } ?>">
            <input type="hidden" name="widget-conditions-visible"
                   value="<?php if ( isset( $_POST['widget-conditions-visible'] ) ) {
				       echo esc_attr( $_POST['widget-conditions-visible'] );
			       } else { ?>0<?php } ?>"/>
			<?php if ( ! isset( $_POST['widget-conditions-visible'] ) ) : ?>
                <a href="#" class="button display-options">
					<?php _e( 'Visibility', self::$textdomain ); ?>
                </a>
			<?php endif; ?>
            <div class="widget-conditional-inner">
                <div class="condition-top">
					<?php printf( _x( '%s if:', 'placeholder: dropdown menu to select widget visibility; hide if or show if', self::$textdomain ), '<select name="conditions[action]"><option value="show" ' . selected( $conditions['action'], 'show', false ) . '>' . esc_html_x( 'Show', 'Used in the "%s if:" translation for the widget visibility dropdown', self::$textdomain ) . '</option><option value="hide" ' . selected( $conditions['action'], 'hide', false ) . '>' . esc_html_x( 'Hide', 'Used in the "%s if:" translation for the widget visibility dropdown', self::$textdomain ) . '</option></select>' ); ?>
                </div><!-- .condition-top -->

                <div class="conditions">
					<?php

					foreach ( $conditions['rules'] as $rule ) {
						?>
                        <div class="condition">
                            <div class="alignleft">
                                <select class="conditions-rule-major" name="conditions[rules_major][]">
                                    <option value="" <?php selected( "", $rule['major'] ); ?>>
										<?php echo esc_html_x( '-- Select --', 'Used as the default option in a dropdown list', self::$textdomain ); ?>
                                    </option>

                                    <option value="category" <?php selected( "category", $rule['major'] ); ?>>
										<?php esc_html_e( 'Category', self::$textdomain ); ?>
                                    </option>

                                    <option value="author" <?php selected( "author", $rule['major'] ); ?>>
										<?php echo esc_html_x( 'Author', 'Noun, as in: "The author of this post is..."', self::$textdomain ); ?>
                                    </option>
                                    <option value="tag" <?php selected( "tag", $rule['major'] ); ?>>
										<?php echo esc_html_x( 'Tag', 'Noun, as in: "This post has one tag."', self::$textdomain ); ?>
                                    </option>
									<?php self::tax_helper_html( $rule['major'] ); ?>
                                    <option value="date" <?php selected( "date", $rule['major'] ); ?>>
										<?php echo esc_html_x( 'Date', 'Noun, as in: "This page is a date archive."', self::$textdomain ); ?>
                                    </option>
                                    <option value="page" <?php selected( "page", $rule['major'] ); ?>>
										<?php echo esc_html_x( 'Page', 'Example: The user is looking at a page, not a post.', self::$textdomain ); ?>
                                    </option>
                                </select>
								<?php _ex( 'is', 'Widget Visibility: {Rule Major [Page]} is {Rule Minor [Search results]}', self::$textdomain ); ?>
                                <select class="conditions-rule-minor" name="conditions[rules_minor][]" <?php if ( ! $rule['major'] ) { ?> disabled="disabled"<?php } ?>
                                        data-loading-text="<?php esc_attr_e( 'Loading...', self::$textdomain ); ?>">
									<?php self::widget_conditions_options_echo( $rule['major'], $rule['minor'] ); ?>
                                </select>
                                <span class="condition-conjunction">
                                    <?php echo esc_html_x( 'or', 'Shown between widget visibility conditions.', self::$textdomain ); ?>
                                </span>
                            </div>
                            <div class="condition-control alignright">
                                <a href="#" class="delete-condition">
									<?php esc_html_e( 'Delete', self::$textdomain ); ?>
                                </a>
                                |
                                <a href="#" class="add-condition">
									<?php esc_html_e( 'Add', self::$textdomain ); ?>
                                </a>
                            </div>
                            <br class="clear"/>
                        </div><!-- .condition -->
						<?php
					}

					?>
                </div><!-- .conditions -->
            </div><!-- .widget-conditional-inner -->
        </div><!-- .widget-conditional -->
		<?php
	}

	/**
	 * On an AJAX update of the widget settings, process the display conditions.
	 *
	 * @param array $new_instance New settings for this instance as input by the user.
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Modified settings.
	 */
	public static function widget_update( $instance, $new_instance, $old_instance ) {
		$conditions           = array();
		$conditions['action'] = $_POST['conditions']['action'];
		$conditions['rules']  = array();

		foreach ( $_POST['conditions']['rules_major'] as $index => $major_rule ) {
			if ( ! $major_rule ) {
				continue;
			}

			$conditions['rules'][] = array(
				'major' => $major_rule,
				'minor' => isset( $_POST['conditions']['rules_minor'][ $index ] ) ? $_POST['conditions']['rules_minor'][ $index ] : ''
			);
		}

		if ( ! empty( $conditions['rules'] ) ) {
			$instance['conditions'] = $conditions;
		} else {
			unset( $instance['conditions'] );
		}

		if ( ( isset( $instance['conditions'] ) && ! isset( $old_instance['conditions'] ) ) || ( isset( $instance['conditions'], $old_instance['conditions'] ) &&
		                                                                                         serialize( $instance['conditions'] ) != serialize( $old_instance['conditions'] )
			)
		) {
			do_action( 'widget_conditions_save' );
		} else if ( ! isset( $instance['conditions'] ) && isset( $old_instance['conditions'] ) ) {
			do_action( 'widget_conditions_delete' );
		}

		return $instance;
	}

	/**
	 * Filter the list of widgets for a sidebar so that active sidebars work as expected.
	 *
	 * @param array $widget_areas An array of widget areas and their widgets.
	 *
	 * @return array The modified $widget_area array.
	 */
	public static function sidebars_widgets( $widget_areas ) {
		$settings = array();
		foreach ( $widget_areas as $widget_area => $widgets ) {
			if ( empty( $widgets ) ) {
				continue;
			}

			if ( 'wp_inactive_widgets' == $widget_area ) {
				continue;
			}

			foreach ( $widgets as $position => $widget_id ) {
				// Find the conditions for this widget.
				list( $basename, $suffix ) = explode( "-", $widget_id, 2 );

				if ( ! isset( $settings[ $basename ] ) ) {
					$settings[ $basename ] = get_option( 'widget_' . $basename );
				}

				if ( isset( $settings[ $basename ][ $suffix ] ) ) {
					if ( false === self::filter_widget( $settings[ $basename ][ $suffix ] ) ) {
						unset( $widget_areas[ $widget_area ][ $position ] );
					}
				}
			}
		}

		return $widget_areas;
	}

	/**
	 * Determine whether the widget should be displayed based on conditions set by the user.
	 *
	 * @param array $instance The widget settings.
	 *
	 * @return array Settings to display or bool false to hide.
	 */
	public static function filter_widget( $instance ) {
		global $post, $wp_query;

		$taxonomies = get_taxonomies( array(
			'public'   => true,
			'_builtin' => false
		) );

		$taxonomies_names = array_keys( $taxonomies );

		if ( empty( $instance['conditions'] ) || empty( $instance['conditions']['rules'] ) ) {
			return $instance;
		}


		foreach ( $instance['conditions']['rules'] as $rule ) {
			if ( $rule['major'] == 'date' ) {
				switch ( $rule['minor'] ) {
					case '':
						$condition_result = is_date();
						break;
					case 'month':
						$condition_result = is_month();
						break;
					case 'day':
						$condition_result = is_day();
						break;
					case 'year':
						$condition_result = is_year();
						break;
				}
			} elseif ( $rule['major'] == 'page' ) {
				// Previously hardcoded post type options.
				if ( 'post' == $rule['minor'] ) {
					$rule['minor'] = 'post_type-post';
				} else if ( ! $rule['minor'] ) {
					$rule['minor'] = 'post_type-page';
				}

				switch ( $rule['minor'] ) {
					case '404':
						$condition_result = is_404();
						break;
					case 'search':
						$condition_result = is_search();
						break;
					case 'archive':
						$condition_result = is_archive();
						break;
					case 'posts':
						$condition_result = $wp_query->is_posts_page;
						break;
					case 'home':
						$condition_result = is_home();
						break;
					case 'front':
						$condition_result = is_front_page();
						break;
					default:
						if ( substr( $rule['minor'], 0, 10 ) == 'post_type-' ) {
							$condition_result = is_singular( substr( $rule['minor'], 10 ) );
						} else {
							// $rule['minor'] is a page ID
							$condition_result = is_page( $rule['minor'] );
						}
						break;
				}
			} elseif ( $rule['major'] == 'tag' ) {
				if ( ! $rule['minor'] && is_tag() ) {

					$condition_result = true;
				} elseif ( $rule['minor'] == 'all' && is_tag() ) {
					$condition_result = true;
				} else if ( is_singular() && $rule['minor'] && has_tag( $rule['minor'] ) ) {
					$condition_result = true;
				} else {
					$tag = get_tag( $rule['minor'] );

					if ( $tag && is_tag( $tag->slug ) ) {
						$condition_result = true;
					}

				}
			} elseif ( $rule['major'] == 'category' ) {
				if ( ! $rule['minor'] && is_category() ) {
					$condition_result = true;
				} elseif ( $rule['minor'] == 'all' && is_category() ) {
					$condition_result = true;
				} else if ( is_category( $rule['minor'] ) ) {
					$condition_result = true;
				} else if ( is_singular() && $rule['minor'] && has_category( $rule['minor'] ) ) {
					$condition_result = true;
				}

			} elseif ( $rule['major'] == 'author' ) {
				if ( ! $rule['minor'] && is_author() ) {
					$condition_result = true;
				} else if ( $rule['minor'] && is_author( $rule['minor'] ) ) {
					$condition_result = true;
				} else if ( is_singular() && $rule['minor'] && $rule['minor'] == $post->post_author ) {
					$condition_result = true;
				}
			} elseif ( array_key_exists( $rule['major'], $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
				    d(
					    $taxonomy, $rule['minor']
				    );
					if ( is_tax( $taxonomy, $rule['minor'] ) || 'all' == $rule['minor'] ) {
						$condition_result = true;
					}
				}
			}


		}

		if ( ( 'show' == $instance['conditions']['action'] && ! $condition_result ) || ( 'hide' == $instance['conditions']['action'] && $condition_result ) ) {
			return false;
		}

		return $instance;
	}


	public static function strcasecmp_name( $a, $b ) {
		return strcasecmp( $a->name, $b->name );
	}

	private static function tax_helper() {
		$taxonomies = get_taxonomies( array(
			'public'   => true,
			'_builtin' => false
		) );

		return $taxonomies;
	}

	public static function tax_helper_html( $current ) {
		$taxonomies = self::tax_helper();
		foreach ( $taxonomies as $taxonomy ):
			$taxonomy_obj = get_taxonomy( $taxonomy );
			?>
            <option value="<?php echo $taxonomy; ?>" <?php selected( $taxonomy_obj->name, $current ); ?>>
				<?php echo $taxonomy_obj->label; ?>
            </option>
			<?php
		endforeach;

	}
}


function bro_widget_visibility_init() {
	BroWidgetVisibility::init( __FILE__ );
}

add_action( 'init', 'bro_widget_visibility_init', 90 );
