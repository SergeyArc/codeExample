<?php

use services\grid\GridTrait;

class Expertises extends Doctrine_Record {

    use GridTrait;
	
	public function setTableDefinition() {
		
		$this->setTableName('eco__expertises');

        $this->hasColumn ( "uuid", "blob");

		$this->hasColumn ('status', 'enum', null, 
			array ( 'values' => array ('queue', 'underway', 'finished', 'suspended', 'issued', 'deleted', 'returned'), 
					'default' => 'queue', 
					'notnull' => true						
			)
		);

		$this->hasColumn('type', 'enum', null,
			array(	'values' => array('expertise', 'investigation'), 
					'default' => 'expertise', 
					'notnull' => true
			)
		);	

		$this->hasColumn('genus', 'enum', null,
				array(	'values' => array('primary', 'repeat', 'additional'),
						'default' => 'primary',
						'notnull' => true
				)
		);	
				
		$this->hasColumn("complex", "boolean");
		$this->hasColumn("commission", "boolean");
		$this->hasColumn("category", "integer");
		$this->hasColumn("initiator_institution", "integer", array('default' => 0));
		$this->hasColumn("initiator_department", "integer", array('default' => 0));
		$this->hasColumn("initiator", "integer");
        $this->hasColumn("questions", "integer", 4);
		
		$this->hasColumn("number", "string", 255);
				
		$this->hasColumn( "findings", "clob" );
		$this->hasColumn( "story", "clob" );

		$this->hasColumn("date", "datetime");
		$this->hasColumn("start_date", "datetime");
		$this->hasColumn("end_date", "datetime");
		$this->hasColumn("suspension_date", "datetime");
		$this->hasColumn("resumption_date", "datetime");
		$this->hasColumn("issued_date", "datetime");

		$this->hasColumn("out_number", "string", 255);
		$this->hasColumn("out_year", "integer", 4);
		$this->hasColumn("department", "integer");

        $this->hasColumn('complexity', 'enum', null,
            array(	'values' => array('light', 'middle', 'difficult', 'heavy'),
                'default' => 'light',
                'notnull' => true
            )
        );

        $this->hasColumn('findings_class', 'enum', null,
            array(	'values' => array('categorical', 'probable', 'impossible'),
                'default' => 'categorical',
                'notnull' => false
            )
        );
		
		$this->index('yearindex', array('fields' => array('number', 'year')));
		$this->index('numberindex', array('fields' => array('number')));
		$this->index('typeindex', array('fields' => array('type')));
		$this->index('initiatorindex', array('fields' => array('initiator')));
		$this->index('outnumberindex', array('fields' => array('out_number')));
	}
	
    public function setUp()
    {
		$this->actAs('Timestampable');
					
		$this->hasOne('Categories', array(
				'local' => 'category',
				'foreign' => 'id'
		));
		
		$this->hasOne('Institutions', array(
				'local' => 'initiator_institution',
				'foreign' => 'id'
		));
		
		$this->hasOne('Departments', array(
				'local' => 'initiator_department',
				'foreign' => 'id'
		));
		
		$this->hasOne('Initiators', array(
				'local' => 'initiator',
				'foreign' => 'id'
		));
		
		$this->hasMany('Experts as Experts', array(
				'local' => 'expertise_id',
				'foreign' => 'expert_id',
				'refClass' => 'ExpertisesExperts'
		));
		
		$this->hasMany('Objects as Objects', array(
				'local' => 'expertise_id',
				'foreign' => 'object_id',
				'refClass' => 'ExpertisesObjects'
		));

		$this->hasMany('Questions as Questions', array(
				'local' => 'id',
				'foreign' => 'expertise_id'
		));
		
		$this->hasMany('ExpertisesActions as Actions', array(
				'local' => 'id',
				'foreign' => 'expertise_id'
		));
		
		$this->hasMany('ObjectsOutsideCategories as ObjectsOutsideCategories', array(
				'local' => 'id',
				'foreign' => 'expertise_id'
		));

		$this->hasOne('AffiliateDepartment as Department', array(
		    'local' => 'department',
		    'foreign' => 'id'
		));

        $this->hasMany('Complexity as ComplexitySigns', array(
            'local' => 'expertise_id',
            'foreign' => 'complexity_id',
            'refClass' => 'ExpertisesComplexity'
        ));

        $this->hasMany('Tags as Tags', array(
            'local' => 'expertise_id',
            'foreign' => 'tag_id',
            'refClass' => 'ExpertisesTags'
        ));
    }

    public function getGridData($rowsPerPage, $firstRowIndex, $sortingField, $sortingOrder, $searchData = [], $mandatoryParams = [])
    {
        try {
            $query = Doctrine_Query::create()
                ->select('e.id, e.type, e.number, e.out_number, e.status, e.date, e.start_date, e.end_date, e.complex, 
                                e.commission, e.findings, e.complexity, e.findings_class,
                                c.shortname, 
                                exp.name, 
                                inst.name, 
                                dep.name, 
                                in.name,                                
                                comp.name,
                                t.name')
                ->from('Expertises e')
                ->leftJoin('e.Experts exp')
                ->leftJoin('e.Categories c')
                ->leftJoin('e.Institutions inst')
                ->leftJoin('e.Departments dep')
                ->leftJoin('e.Initiators in')
                ->leftJoin('e.Tags t')
                ->leftJoin('e.ComplexitySigns comp INDEXBY comp.id');

            $this->getWhereClause($query, $searchData);

            if (!empty($searchData['groups'])) {
                foreach ($searchData['groups'] as $searchGroup) {
                    $this->getWhereClause($query, $searchGroup);
                }
            }

            $this->addMandatoryParams($query, $mandatoryParams);
            $countQuantity = $this->countQueryData($query);

            $query->limit($rowsPerPage);
            $query->offset($firstRowIndex);
            $query->orderBy($sortingField . ' ' . $sortingOrder . ', created_at ' . $sortingOrder);

            return [
                "quantity" => $countQuantity,
                "data" => $query->execute()->toArray()
            ];
        } catch (Doctrine_Query_Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}