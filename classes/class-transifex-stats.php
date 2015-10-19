<?php

class Codepress_Transifex_Stats {

	public $project_slug, $resource_slug, $api;

	public function __construct( $project_slug, $resource_slug = '', $cache_time = 3600 ) {

		$this->project_slug  = $project_slug;
		$this->resource_slug = $resource_slug;
		$this->cache_time 	 = $cache_time;
		$this->api = new Codepress_Transifex_API( $cache_time );
	}

	public function get_project() {
		return $this->api->connect_api( "project/{$this->project_slug}?details" );
	}
	public function get_language( $language_code ) {
		return $this->api->connect_api( "language/{$language_code}" );
	}
	public function get_translators( $team_language ) {
		return $this->api->connect_api( "project/{$this->project_slug}/language/{$team_language}/" );
	}
	public function get_languages() {
		return $this->api->connect_api( "project/{$this->project_slug}/languages/" );
	}

	public function sort_objects_by_completion( $b, $a ) {
		if ( (int) $a->completed == (int) $b->completed ) return 0 ;
		return ( (int) $a->completed < (int) $b->completed) ? -1 : 1;
	}

	public function maybe_error( $response ) {

		$error = false;
		if ( ! $response ) {
			$error = __('No results', 'transifex-stats' );
		}
		if ( is_string( $response ) ) {
			$error = $response;
		}
		if ( is_array( $response ) && isset( $response['error'] ) ) {
			$error = $response['error']['message'] . ' (' . $response['error']['code'] . ')';
		}
		return $error;
	}

	/**
	 * Display users that contributed a translation to the project
	 *
	 * @since 1.1
	 *
	 * @return array API result
	 */
	public function display_contributors() {

		$languages = $this->get_languages();
echo '<pre>'; print_r( $languages ); echo '</pre>'; exit;
		if ( $error = $this->maybe_error( $languages ) ) {
			echo $error;
			return;
		}

		$translators = array();
		$translators_per_language = array();

		if ( $languages ) {
			foreach ( $languages as $lang ) {

				$contributors = array();
				if ( ! empty( $lang->translators ) ) {
					$contributors = array_merge( $contributors, $lang->translators );
				}
				if ( ! empty( $lang->coordinators ) ) {
					$contributors = array_merge( $contributors, $lang->coordinators );
				}
				if ( ! empty( $lang->reviewers ) ) {
					$contributors = array_merge( $contributors, $lang->reviewers );
				}
				if ( empty( $contributors ) ) {
					continue;
				}

				$translators_per_language[ $lang->language_code ] = $contributors;
				$translators = array_unique( array_merge( $contributors, $translators ) );
			}
		}

		echo '<pre>'; print_r( $translators ); echo '</pre>'; //exit;
		echo '<pre>'; print_r( $translators_per_language ); echo '</pre>'; exit;

		if ( ! $translators ) {
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
		if ( $error = $this->maybe_error( $project ) ) {
			echo $error;
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
		if ( $this->maybe_error( $languages ) ) {
			return;
		}

		// sort stats by completion
		$languages = (array) $languages;
		uasort( $languages, array( $this, 'sort_objects_by_completion' ) );

		$languages = apply_filters( 'cpti_transifex_stats', $languages, $project );

		if ( $languages ) : ?>

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
						<a target="_blank" href="https://www.transifex.com/projects/p/<?php echo $this->project_slug; ?>/translate/#<?php echo $language_code; ?>"><?php _e( 'Translate', 'transifex-stats' ); ?></a>
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