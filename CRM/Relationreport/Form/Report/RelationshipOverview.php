<?php
/*-------------------------------------------------------+
| Relationship Reports                                   |
| Copyright (C) 2015 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Relationreport_Form_Report_RelationshipOverview extends CRM_Report_Form {

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  protected $_customGroupGroupBy = FALSE; 

  function __construct() {
    // create list of relationship types
    $relationshipTypes = array();
    $relationshipTypeParams = array(
      'is_active' => 1,
      'options' => ['limit' => 0, 'sort' => "label_a_b ASC"],
    );
    $query = civicrm_api3('RelationshipType', 'get', $relationshipTypeParams);
    foreach ($query['values'] as $relationshipType) {
      $key = "relationship_{$relationshipType['id']}_";
      $relationshipTypes[$key.'a_b'] = array(
        'title'   => $relationshipType['label_a_b'],
        'type'    => CRM_Utils_Type::T_STRING,
      );

      $relationshipTypes[$key.'b_a'] = array(
        'title'   => $relationshipType['label_b_a'],
        'type'    => CRM_Utils_Type::T_STRING,
      );
    }

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name', array('domain' => 'de.systopia.relationreport')),
            'required'  => TRUE,
            'default'   => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'tags' => array(
            'title' => ts('Tags', array('domain' => 'de.systopia.relationreport')),
            'no_repeat' => TRUE,
          ),
          // TODO: additional fields?
          // 'first_name' => array(
          //   'title' => ts('First Name', array('domain' => 'de.systopia.relationreport')),
          //   'no_repeat' => TRUE,
          // ),
          // 'last_name' => array(
          //   'title' => ts('Last Name', array('domain' => 'de.systopia.relationreport')),
          //   'no_repeat' => TRUE,
          // ),
        ) + $relationshipTypes,
      ),
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Relationship Overview Report', array('domain' => 'de.systopia.relationreport')));
    parent::preProcess();
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) || CRM_Utils_Array::value($fieldName, $this->_params['fields'])) {
            if ('relationship_' == substr($fieldName, 0, 13)) {
              // this is a relationship field
              $relationship_direction = substr($fieldName, -1, 1);
              $relationship_table     = $fieldName . '_table'; 
              $contact_table          = $fieldName . '_contact_table'; 
              // $select[] = " GROUP_CONCAT(DISTINCT({$relationship_table}.contact_id_{$relationship_direction}) SEPARATOR ',') AS {$tableName}_{$fieldName} ";
              $select[] = " GROUP_CONCAT(DISTINCT({$contact_table}.display_name) SEPARATOR ', ') AS {$tableName}_{$fieldName} ";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value('type', $field);

            } elseif ('tags' == $fieldName) {
              // tags should be included as an aggregated field as well
              $select[] = " GROUP_CONCAT(DISTINCT(tag.name) SEPARATOR ', ') AS {$tableName}_{$fieldName} ";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value('type', $field);

            } else {
              // default field            
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value('type', $field);
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
    // error_log($this->_select);
  }

  function from() {
    $this->_from = " FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom} ";

    // JOIN relationsship table
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) || CRM_Utils_Array::value($fieldName, $this->_params['fields'])) {
            if ('relationship_' == substr($fieldName, 0, 13)) {
              $relationship_type   = substr($fieldName, 13, -4);
              $relationship_source = substr($fieldName, -3, 1);
              $relationship_target = substr($fieldName, -1, 1);
              $relationship_table  = $fieldName . '_table'; 
              $this->_from .= " LEFT JOIN civicrm_relationship {$relationship_table}    ON {$relationship_table}.contact_id_{$relationship_source} = {$this->_aliases['civicrm_contact']}.id AND {$relationship_table}.relationship_type_id = {$relationship_type} AND {$relationship_table}.is_active = 1 ";

              // join contact table for the display name
              $contact_table = $fieldName . '_contact_table'; 
              $this->_from .= " LEFT JOIN civicrm_contact {$contact_table} ON {$relationship_table}.contact_id_{$relationship_target} = {$contact_table}.id ";
            }
          }
        }
      }
    }


    // JOIN tags
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if ($fieldName == 'tags') {
            $contact_table = $fieldName . '_contact_table'; 
            $this->_from .= " LEFT JOIN civicrm_entity_tag et  ON et.entity_id = {$this->_aliases['civicrm_contact']}.id AND et.entity_table = 'civicrm_contact' ";
            $this->_from .= " LEFT JOIN civicrm_tag        tag ON et.tag_id    = tag.id ";
            break 2;
          }
        }
      }
    }

    // error_log($this->_from);
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    // error_log($this->_where);
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_contact']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id";
  }


  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);
    // error_log($sql);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }


  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
      }
    }
  }

}
