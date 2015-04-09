<?php

/**
 * SSSEO_OpenGraph_SiteTree_DataExtension
 *
 * @todo add description
 *
 * @namespace SSSEO
 * @package Open Graph
 * @author Andrew Gerber <atari@graphiquesdigitale.net>
 * @version 1.0.0
 *
 * @todo lots
 *
 */

class SSSEO_OpenGraph_SiteTree_DataExtension extends DataExtension {

	//// Model Variables
	private static $db = array(
		// HTML
		'OpenGraphType' => 'Enum(array("off", "article"), "off")',
		'OpenGraphTitle' => 'Text',
		'OpenGraphDescription' => 'Text',
	);
	private static $has_one = array(
		'OpenGraphImage' => 'Image'
	);


	/* Overload Methods
	------------------------------------------------------------------------------*/

	// CMS Fields
	public function updateCMSFields(FieldList $fields) {

		//
		$config = SiteConfig::current_site_config();

		// SSSEO Tabset
// 		$fields->addFieldToTab('Root', new TabSet('SSSEO'));

		// Open Graph tab
		$tab = 'Root.SSSEO.OpenGraph';

		//// Type
		$fields->addFieldsToTab($tab, array(
			DropdownField::create('OpenGraphType', 'og:type', $this->owner->dbObject('OpenGraphType')->enumValues()),
		));

		// if NOT off
		if ($this->owner->OpenGraphType != 'off') {
			//
			$fields->addFieldsToTab($tab, array(
				ReadonlyField::create('ReadonlyOpenGraphSiteName', 'og:site_name', $config->Title),
				TextField::create('OpenGraphTitle', 'og:title')
					->setAttribute('placeholder', $this->owner->Title),
				ReadonlyField::create('ReadonlyOpenGraphURL', 'og:url', $this->owner->AbsoluteLink()),
				TextareaField::create('OpenGraphDescription', 'og:description')
					->setAttribute('placeholder', $this->owner->MetaDescription),
				UploadField::create('OpenGraphImage', 'og:image')
					->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'))
					->setFolderName('SSSEO/OpenGraph/')
					->setDescription('file format: JPG, PNG, GIF<br />pixel dimensions: 1200 x 630')
			));
			//
// 			if ($this->owner->hasExtension('SSSEO_Authorship_SiteTree_DataExtension')
// 					&& $config->hasExtension('SSSEO_Authorship_SiteConfig_DataExtension')
// 					&& Member::has_extension('SSSEO_Authorship_Member_DataExtension'))
// 			{

// 			}
		} else {
			//
			$tabset = $fields->findOrMakeTab($tab);
			$tabset->addExtraClass('error');
		}

	}


	/* Template Methods
	------------------------------------------------------------------------------*/

	/**
	 * @name OpenGraphMetadata
	 */
	public function OpenGraphMetadata() {

		$self = $this->owner;

		if ($self->OpenGraphType != 'off') {

			// variables
			$config = SiteConfig::current_site_config();
			$metadata = '<!-- Open Graph -->' . PHP_EOL;

			//// Type

			$metadata .= $self->MarkupFacebook('og:type', $self->OpenGraphType, false);

			//// Site Name

			$metadata .= $self->MarkupFacebook('og:site_name', $config->Title, true);

			//// URL

			$metadata .= $self->MarkupFacebook('og:url', $self->AbsoluteLink(), false);

			//// Title

			$title = ($self->OpenGraphTitle) ? $self->OpenGraphTitle : $self->Title;
			$metadata .= $self->MarkupFacebook('og:title', $title, true);

			//// Description

			$description = ($self->OpenGraphDescription) ? $self->OpenGraphDescription : $self->MetaDescription;
			$metadata .= $self->MarkupFacebook('og:description', $description, true);

			//// Image

			if ($this->owner->OpenGraphImage()->exists()) {
				$metadata .= $self->MarkupFacebook('og:image', $self->OpenGraphImage()->getAbsoluteURL(), false);
			}

			//// fb:app_id

			//// og:locale

			//// article:author

			//// article:publisher

			// return
			return $metadata;

		} else {

			return false;

		}

	}


	/* Class Methods
	------------------------------------------------------------------------------*/

	// none

}