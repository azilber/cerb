<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_ProfilesContactPerson extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$request = DevblocksPlatform::getHttpRequest();
		$translate = DevblocksPlatform::getTranslationService();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$stack = $request->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // calendar_event
		@$id = intval(array_shift($stack));

		if(false == ($person = DAO_ContactPerson::get($id)))
			return;
		
		$tpl->assign('person', $person);

		// Remember the last tab/URL
		
		$point = 'cerberusweb.contact_person.tab';
		$tpl->assign('point', $point);

		// Properties
			
		$properties = array();
			
		if($person->email_id) {
			if(null != ($address = $person->getPrimaryAddress())) {
				$properties['primary_email'] = array(
					'label' => ucfirst($translate->_('common.email')),
					'type' => null,
					'address' => $address,
				);
			}
		}
			
		$properties['created'] = array(
			'label' => ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $person->created,
		);
			
		$properties['last_login'] = array(
			'label' => ucfirst($translate->_('dao.contact_person.last_login')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $person->last_login,
		);
			
		
		// Custom Fields

		@$values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_CONTACT_PERSON, $person->id)) or array();
		$tpl->assign('custom_field_values', $values);
		
		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields(CerberusContexts::CONTEXT_CONTACT_PERSON, $values);
		
		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);
		
		// Custom Fieldsets

		$properties_custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets(CerberusContexts::CONTEXT_CONTACT_PERSON, $person->id, $values);
		$tpl->assign('properties_custom_fieldsets', $properties_custom_fieldsets);
		
		// Link counts
		
		$properties_links = array(
			CerberusContexts::CONTEXT_CONTACT_PERSON => array(
				$person->id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_CONTACT_PERSON,
						$person->id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			),
		);
		
		if(isset($person->email_id)) {
			$properties_links[CerberusContexts::CONTEXT_ADDRESS] = array(
				$person->email_id => 
					DAO_ContextLink::getContextLinkCounts(
						CerberusContexts::CONTEXT_ADDRESS,
						$person->email_id,
						array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
					),
			);
		}
		
		$tpl->assign('properties_links', $properties_links);
		
		// Properties
		
		$tpl->assign('properties', $properties);
		
		// Tabs
		$tab_manifests = Extension_ContextProfileTab::getExtensions(false, CerberusContexts::CONTEXT_CONTACT_PERSON);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::profiles/contact_person.tpl');
	}
};