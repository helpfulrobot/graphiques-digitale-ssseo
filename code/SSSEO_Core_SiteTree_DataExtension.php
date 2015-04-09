<?php

/**
 * SSSEO_Metadata_SiteTree_DataExtension
 *
 * @todo add description
 *
 * @package SSSEO
 * @subpackage Main
 * @author Andrew Gerber <atari@graphiquesdigitale.net>
 * @version 1.0.0
 *
 * @todo lots
 *
 */

class SSSEO_Core_SiteTree_DataExtension extends DataExtension {

	/* Overload Variable
	 ------------------------------------------------------------------------------*/

	private static $db = array(
		// HTML
		'MetaDescription' => 'Text', // redundant, but included for backwards-compatibility
		'ExtraMeta' => 'HTMLText', // redundant, but included for backwards-compatibility
	);
	private static $many_many = array(
		'Authors' => 'Member',
	);


	/* Overload Methods
	------------------------------------------------------------------------------*/

	// CMS Fields
	public function updateCMSFields(FieldList $fields) {

		// variables
		$config = SiteConfig::current_site_config();
		$self = $this->owner;

		// SSSEO Tabset
		$fields->addFieldToTab('Root', new TabSet('SSSEO'));

		// remove
		$fields->removeByName(array('Metadata'));

		//// Full Output

		$tab = 'Root.SSSEO.FullOutput';

		$fields->addFieldsToTab($tab, array(
			LiteralField::create('LiteralFullOutput', '<pre>' . nl2br(htmlentities($self->Metadata(), ENT_QUOTES)) . '</pre>')
		));

		//// Metadata

		$tab = 'Root.SSSEO.Metadata';

		// MetaCanonical
		if ($config->CanonicalEnabled()) {
			$fields->addFieldsToTab($tab, array(
				ReadonlyField::create('ReadonlyMetaCanonical', 'link rel="canonical"', $self->MetaCanonical())
			));
		}

		// MetaTitle
		if ($config->TitleEnabled()) {
			$fields->addFieldsToTab($tab, array(
				ReadonlyField::create('ReadonlyMetaTitle', 'meta title', $self->MetaTitle())
			));
		}

		// MetaDescription
		$fields->addFieldsToTab($tab, array(
			TextareaField::create('MetaDescription', 'meta description')
				->setAttribute('placeholder', $self->MetaContent())
		));

		// ExtraMeta
		if ($config->ExtraMetaEnabled()) {
			$fields->addFieldsToTab($tab, array(
				TextareaField::create('ExtraMeta', 'Custom Metadata')
			));
		}

		//// Authorship

		$tab = 'Root.SSSEO.Authors';

		// Authors
		if ($config->AuthorshipEnabled()) {

			$fields->addFieldsToTab($tab, array(
			GridField::create('Authors', 'Authors', $self->Authors())
				->setConfig(GridFieldConfig_RelationEditor::create())
			));
		}

	}


	/* Template Methods
	------------------------------------------------------------------------------*/

	/**
	 * @name Metadata
	 */
	public function Metadata() {

		// variables
		$config = SiteConfig::current_site_config();
		$self = $this->owner;
		$metadata = PHP_EOL . '<!-- SSSEO -->' . PHP_EOL;

		//// Basic
		$metadata .= '<!-- HTML -->' . PHP_EOL;

		// Charset
		if ($config->CharsetEnabled()) {
			$metadata .= '<meta charset="' . $config->Charset . '" />' . PHP_EOL;
		}

		// Canonical
		if ($config->CanonicalEnabled()) {
			$metadata .= '<link rel="canonical" href="' . $self->MetaCanonical() . '" />' . PHP_EOL;
		}

		// Title
		if ($config->TitleEnabled()) {
			$metadata .= '<title>' . htmlentities($self->MetaTitle(), ENT_QUOTES) . '</title>' . PHP_EOL;
		}

		// Description
		$metadata .= $self->Markup('description', htmlentities($self->MetaDescription(), ENT_QUOTES));

		// Favicon
		if ($config->FaviconEnabled()) {

			$ico = Director::fileExists('favicon.ico');

			if ($ico) {

			}

			// PNG + ICO
			if ($config->FaviconPNG()->exists()) {

				//
				$pngURL = $config->FaviconPNG()->SetSize(152, 152)->getAbsoluteURL();
				$pngBG = ($config->FaviconBG) ? $config->FaviconBG : $config->faviconBGDefault();

				//
				$metadata .= '<!-- Favicon -->' . PHP_EOL;

				// 1. favicon.png
				$metadata .= '<link rel="icon" href="' . $pngURL . '" />' . PHP_EOL;

				// 2. favicon.ico
				if ($ico) {
					// IE all-but-10
					$metadata .= '<!--[if IE]><link rel="shortcut icon" href="/favicon.ico" /><![endif]-->' . PHP_EOL;
				}

				// IE 10
				$metadata .= '<meta name="msapplication-TileColor" content="#' . $pngBG . '" />' . PHP_EOL;
				$metadata .= '<meta name="msapplication-TileImage" content="' . $pngURL . '" />' . PHP_EOL;

			}

			// ICO only
			else {
				if ($ico) {
					$metadata = '<!-- Favicon -->' . PHP_EOL;
					$metadata .= '<link rel="shortcut icon" href="/favicon.ico" />' . PHP_EOL;
				}
			}

		}

		//// Authorship

		$authors = $self->Authors();
		$metadata .= '<!-- Authorship -->' . PHP_EOL;

		// Google+ Authors
		foreach ($authors as $author) {
			if ($author->GoogleProfileID) {
				$metadata .= '<link rel="author" href="https://plus.google.com/' . $author->GoogleProfileID . '/" />' . PHP_EOL;
				// @todo kinda - Google+ does not support multiple authors - break loop
				break;
			}

		}

		// Google+ Publisher
		if ($config->GoogleProfileID) {
			$metadata .= '<link rel="publisher" href="https://plus.google.com/' . $config->GoogleProfileID . '/" />' . PHP_EOL;
		}

		// Facebook Authors
		foreach ($authors as $author) {
			if ($author->FacebookProfileID) {
				$metadata .= '<meta property="article:author" content="' . $author->FacebookProfileID . '" />' . PHP_EOL;
			}
		}

		// Facebook Publisher
		if ($config->FacebookProfileID) {
			$metadata .= '<meta property="article:publisher" content="' . $config->FacebookProfileID . '" />' . PHP_EOL;
		}

		//// Facebook Insights

		if ($config->hasExtension('SSSEO_FacebookInsights_SiteConfig_DataExtension')) {
			$metadata .= $config->FacebookInsightsMetadata();
		}

		//// Open Graph

		if ($self->hasExtension('SSSEO_OpenGraph_SiteTree_DataExtension')) {
			$metadata .= $self->OpenGraphMetadata();
		}

		//// Twitter Cards

		if ($self->hasExtension('SSSEO_TwitterCards_SiteTree_DataExtension')) {
			$metadata .= $self->TwitterCardsMetadata();
		}

		//// ExtraMeta

		if ($config->ExtraMetaEnabled()) {
			if ($extraMeta = $this->MetaExtraMeta()) {
				$metadata .= '<!-- Extra Metadata --->' . PHP_EOL;
				$metadata .= $this->MetaExtraMeta() . PHP_EOL;
			}
		}

		// end
		$metadata .= '<!-- end SSSEO -->' . PHP_EOL;

		// return
		return $metadata;

	}


	/* Helper Methods
	------------------------------------------------------------------------------*/

	/**
	 * @name Markup
	 */
	public function Markup($name, $content, $encode = true) {
		// encode content
		if ($encode) $content = htmlentities($content, ENT_QUOTES);
		// return
		return '<meta name="' . $name . '" content="' . $content . '" />' . PHP_EOL;
	}

	/**
	 * @name MarkupRel
	 */
	public function MarkupRel($name, $content, $encode = true) {
		// encode content
		if ($encode) $content = htmlentities($content, ENT_QUOTES);
		// return
		return '<meta name="' . $name . '" content="' . $content . '" />' . PHP_EOL;
	}

	/**
	 * @name FacebookMarkup
	 */
	public function MarkupFacebook($property, $content, $encode = true) {
		// encode content
		if ($encode) $content = htmlentities($content, ENT_QUOTES);
		//
		return '<meta property="' . $property . '" content="' . $content . '" />' . PHP_EOL;
	}

	/**
	 * @name TwitterMarkup
	 */
	public function MarkupTwitter($name, $content, $encode = true) {
		// encode content
		if ($encode) $content = htmlentities($content, ENT_QUOTES);
		// return
		return '<meta name="' . $name . '" content="' . $content . '" />' . PHP_EOL;
	}


	/* Meta Methods
	------------------------------------------------------------------------------*/

	/**
	 * @name MetaCharset
	 */
	public function MetaCharset() {

		// variables
		$config = SiteConfig::current_site_config();

		//
		return $config->Charset;

	}

	/**
	 * @name MetaCanonical
	 */
	public function MetaCanonical() {

		return $this->owner->AbsoluteLink();

	}

	/**
	 * @name MetaTitle
	 * default limit: 70 characters
	 */
	public function MetaTitle($length = 70) {

		// variables
		$config = SiteConfig::current_site_config();

		// collect title parts
		$titles = array();
		// Title WHERE TitlePosition = first
		if ($config->TitlePosition == 'first' && $config->Title) {
			$titleSeparator = ($config->TitleSeparator) ? $config->TitleSeparator : $config->titleSeparatorDefault();
			array_push($titles, $config->Title);
			array_push($titles, $titleSeparator);
		}
		// Title
		if ($this->owner->Title) {
			array_push($titles, $this->owner->Title);
		}
		// Tagline
		if ($config->Tagline) {
			$taglineSeparator = ($config->TaglineSeparator) ? $config->TaglineSeparator : $config->taglineSeparatorDefault();
			array_push($titles, $taglineSeparator);
			array_push($titles, $config->Tagline);
		}
		// Title WHERE TitlePosition = last
		if ($config->TitlePosition == 'last' && $config->Title) {
			$titleSeparator = ($config->TitleSeparator) ? $config->TitleSeparator : $config->titleSeparatorDefault();
			array_push($titles, $titleSeparator);
			array_push($titles, $config->Title);
		}

		// implode to create title
		$title = implode(' ', $titles);

		// return
// 			return substr($title, 0, $length);
		return $title;

	}

	/**
	 * @name MetaContent
	 * no limit
	 *
	 * returns first paragraph of page content
	 */
	public function MetaContent() {

		//
		$content = null;

		// content
		if ($content = trim($this->owner->Content)) {
			if (preg_match( '/<p>(.*?)<\/p>/i', $content, $match)) {
				$content = $match[0];
			} else {
				$content = explode("\n", $content);
				$content = $content[0];
			}
		}

		// return
		if ($content) {
			// found - strip & decode value
			return html_entity_decode(strip_tags($content));
		} else {
			// not found
			return false;
		}

	}

	/**
	 * @name MetaDescription
	 * default limit: 155 characters
	 */
	public function MetaDescription($length = 155) {

		$description = null;

		//
		if ($this->owner->MetaDescription) {
			$description = $this->owner->MetaDescription;
		} else {
			$description = $this->MetaContent();
		}

		// return
		if ($description) {
			// found - truncate value
// 			return substr($description, 0, $length);
			return $description;
		} else {
			// not found
			return false;
		}

	}

	/**
	 * @name MetaExtraMeta
	 */
	public function MetaExtraMeta() {

		return $this->owner->ExtraMeta;

	}

}