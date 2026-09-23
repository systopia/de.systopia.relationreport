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

return [
  0 => 
    [
      'name' => 'CRM_Relationreport_Form_Report_RelationshipOverview',
      'entity' => 'ReportTemplate',
      'params' => 
      [
        'version' => 3,
        'label' => ts("Relationship Overview", ['domain' => 'de.systopia.relationreport']),
        'description' => ts("Comprehensive overview a contact's relationships", ['domain' => 'de.systopia.relationreport']),
        'class_name' => 'CRM_Relationreport_Form_Report_RelationshipOverview',
        'report_url' => 'de.systopia.relationreport/relationshipoverview',
        'component' => '',
      ],
    ],
];
