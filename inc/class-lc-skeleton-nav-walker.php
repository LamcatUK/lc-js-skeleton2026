<?php
/**
 * Lightweight nav walker. Outputs nav-link/dropdown-menu class names (kept
 * for familiarity) but has none of Bootstrap's navwalker complexity — no
 * linkmod/icon handling, no Bootstrap 4/5 branching. Submenus are shown via
 * dropdown-toggle buttons, which are accessible and work with keyboard navigation.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LC_JS_Skeleton_Nav_Walker' ) ) {

	/**
	 * Custom nav walker.
	 */
	class LC_JS_Skeleton_Nav_Walker extends Walker_Nav_Menu {

		/**
		 * Holds the id of the submenu currently being opened, so start_lvl()
		 * can target the same id the preceding start_el() pointed its
		 * dropdown-toggle button's aria-controls at.
		 *
		 * @var string
		 */
		protected $current_submenu_id = '';

		/**
		 * Starts the list before the elements are added.
		 *
		 * @param string   $output Passed by reference.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu args.
		 * @return void
		 */
		public function start_lvl( &$output, $depth = 0, $args = null ) {
			$output .= '<ul class="dropdown-menu" id="' . esc_attr( $this->current_submenu_id ) . '">';
		}

		/**
		 * Ends the list after the elements are added.
		 *
		 * @param string   $output Passed by reference.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu args.
		 * @return void
		 */
		public function end_lvl( &$output, $depth = 0, $args = null ) {
			$output .= '</ul>';
		}

		/**
		 * Starts the element output.
		 *
		 * @param string   $output Passed by reference.
		 * @param WP_Post  $item   Menu item.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu args.
		 * @param int      $id     Menu item ID.
		 * @return void
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$has_children = in_array( 'menu-item-has-children', $item->classes, true );
			$is_current   = in_array( 'current-menu-item', $item->classes, true );

			$li_classes = array( 'nav-item' );
			if ( $has_children ) {
				$li_classes[] = 'dropdown';
			}

			$output .= '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '">';

			if ( $has_children ) {
				// Dropdown parents never navigate — the whole item is the toggle.
				$this->current_submenu_id = 'dropdown-' . $item->ID;
				$output                  .= '<button type="button" class="nav-link dropdown-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="' . esc_attr( $this->current_submenu_id ) . '">';
				$output                  .= '<span>' . esc_html( $item->title ) . '</span>';
				$output                  .= '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" /></svg>';
				$output                  .= '</button>';
			} else {
				$link_classes = array( 'nav-link' );
				if ( $is_current ) {
					$link_classes[] = 'active';
				}

				$output .= '<a class="' . esc_attr( implode( ' ', $link_classes ) ) . '" href="' . esc_url( $item->url ) . '"';
				if ( $is_current ) {
					$output .= ' aria-current="page"';
				}
				$output .= '>' . esc_html( $item->title ) . '</a>';
			}
		}

		/**
		 * Ends the element output.
		 *
		 * @param string   $output Passed by reference.
		 * @param WP_Post  $item   Menu item.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu args.
		 * @return void
		 */
		public function end_el( &$output, $item, $depth = 0, $args = null ) {
			$output .= '</li>';
		}
	}
}
