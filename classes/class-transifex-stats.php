<?php

class Codepress_Transifex_Stats {

	public $project_slug, $resource_slug, $api;

	function __construct( $project_slug, $resource_slug = '', $cache_time = 3600 ) {

		$this->project_slug  = $project_slug;
		$this->resource_slug = $resource_slug;
		$this->cache_time 	 = $cache_time;
		$this->api = new Codepress_Transifex_API( $cache_time );
	}

	/**
	 * Get project resources
	 *
	 * @since 1.0
	 *
	 * @return array API result
	 */
	function get_project() {
		return $this->api->connect_api( "project/{$this->project_slug}?details" );
	}

	/**
	 * Getlanguage
	 *
	 * @since 1.0
	 *
	 * @param string $language_code Transifex language code
	 * @return array API result
	 */
	function get_language( $language_code ) {
		return $this->api->connect_api( "language/{$language_code}" );
	}

	/**
	 * Sort object by property
	 *
	 * @since 1.0
	 */
	function sort_objects_by_completion( $b, $a ) {
		if ( (int) $a->completed == (int) $b->completed ) return 0 ;
		return ( (int) $a->completed < (int) $b->completed) ? -1 : 1;
	}

	/**
	 * Is error
	 *
	 * @since 1.0
	 *
	 * @param string $response API response
	 * @return bool Error
	 */
	function maybe_display_error( $response ) {

		$error = '';

		if ( ! $response ) {
			$error = __('No results', 'transifex-stats' );
		}

		if ( is_array( $response ) && isset( $response['error'] ) ) {
			$error = $response['error']['message'] . ' (' . $response['error']['code'] . ')';
		}

		if ( ! $error ) {
			return false;
		}

		echo $error;
		return true;
	}

	/**
	 * Display users that contributed a translation to the project
	 *
	 * @since 1.1
	 *
	 * @return array API result
	 */
	function display_contributors() {

		$project = $this->get_project();
		if ( $this->maybe_display_error( $project ) ) {
			return;
		}

		$contributors = array();
		if ( ! empty( $project->teams ) ) {
			foreach( $project->teams as $team_language ) {
				$translators = $this->api->connect_api( "project/{$this->project_slug}/language/{$team_language}/" );
				if ( ! empty( $translators->translators ) ) {
					$contributors = array_merge( $contributors, $translators->translators );
				}
				if ( ! empty( $translators->coordinators ) ) {
					$contributors = array_merge( $contributors, $translators->coordinators );
				}
				if ( ! empty( $translators->reviewers ) ) {
					$contributors = array_merge( $contributors, $translators->reviewers );
				}
			}
		}

		if ( ! $contributors ) {
			return;
		}

		$contributors = array_unique( $contributors );
		foreach ( $contributors as $username ) { ?>
			<a  class="transifex-contributor" rel="nofollow external" href="https://www.transifex.com/accounts/profile/<?php echo $username; ?>"><?php echo $username; ?></a>
			<?php
		}
	}

	/**
	 * Display stats about translation progress
	 *
	 * @since 1.0
	 *
	 * @param string $project_slug Transifex Project slug
	 * @param string $resource_slug Transifex Resource slug
	 */
	public function display_translations_progress() {

		if ( ! $this->project_slug ) {
			return;
		}

		$project = $this->get_project();
		if ( $this->maybe_display_error( $project ) ) {
			return;
		}

		// get first resource from project if left empty
		$resource_slug = $this->resource_slug;
		if ( ! $resource_slug ) {
			if ( empty( $project->resources ) ) {
				return;
			}
			$resource_slug = $project->resources[0]->slug;
		}

		$languages = $this->api->connect_api( "project/{$this->project_slug}/resource/{$resource_slug}/stats/" );

		if ( $this->maybe_display_error( $languages ) ) {
			return;
		}

		// sort stats by completion
		$languages = (array) $languages;
		uasort( $languages, array( $this, 'sort_objects_by_completion' ) );

		$languages = apply_filters( 'cpti_transifex_stats', $languages, $project );

		if ( $languages ) :
		?>

		<?php if ( $project_title = apply_filters( 'cpti_project_title', $project->name ) ) : ?>
		<div class="transifex-title"><?php echo apply_filters( 'the_title', $project_title ); ?></div>
		<?php endif; ?>
		<ul>
			<?php
			foreach ( $languages as $language_code => $resource ) :
				$language = $this->get_language( $language_code );
			?>
			<li class="clearfix">
				<div class="language_name">
					<?php echo $language->name; ?>
				</div>
				<div class="statbar">
					<div class="graph_resource">
						<div class="translated_comp" style="width:<?php echo $resource->completed; ?>;"></div>
					</div>
					<div class="stats_string_resource">
						<?php echo $resource->completed; ?>
					</div>
					<div class="go_translate">
						<a target="_blank" href="https://www.transifex.com/projects/p/<?php echo $project_slug; ?>/translate/#<?php echo $language_code; ?>"><?php _e( 'Translate', 'transifex-stats' ); ?></a>
					</div>
				</div>
			</li>
			<?php  endforeach; ?>
		</ul>

		<?php
		endif;
	}
}

new Codepress_Transifex();