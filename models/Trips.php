<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use app\helpers\EmailHelper;
use app\helpers\CurrencyHelper;
use yii\helpers\FileHelper;
use yii\web\ForbiddenHttpException;
use app\models\FirstAmounts;
use app\models\TripsTypes;
use app\models\Countries;
use app\models\Flights;
use app\models\Expenses;
use app\models\Users;
use app\models\FilesList;

/**
 * This is the model class for table "{{%trips}}".
 *
 * @property int $id
 * @property int $active
 * @property int $status
 * @property int $users_id
 * @property int $trips_types_id
 * @property int $countries_id
 * @property string $total_nis
 * @property int $plant
 * @property string $city
 * @property string $user_note
 * @property string $checker_note
 * @property string $departure_date
 * @property string $arrival_date
 * @property string $created_at
 * @property string $updated_at
 */
class Trips extends ActiveRecord
{
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

	const SCENARIO_NEW = 'create';
	const SCENARIO_SAVE = 'update';
	const SCENARIO_REMOVE = 'remove';
	const SCENARIO_SEARCH = 'search';
	const SCENARIO_MANAGER_SEND = 'manager_send';
	const SCENARIO_UPLOAD = 'upload';

	public $manager_type;
	public $filesList;
	public $after_save = true;

	protected $rules_of_change = [
		0 => [
			'user_trip_update' => true,
			'user_trip_delete' => true,
			'user_trip_send' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_flights_send' => true,
			'manager_trip_send' => true,
			//'treasurer_trip_edit' => true,
		],
		1 => [
			'user_trip_update' => true,
			//'user_trip_delete' => true,
			'user_trip_send' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_flights_send' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			//'treasurer_trip_edit' => true,
		],
		2 => [
			'user_trip_update' => true,
			'user_trip_delete' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'manager_trip_send' => true,
			//'treasurer_trip_edit' => true,
		],
		3 => [
			'user_trip_update' => true,
			'user_trip_delete' => true,
			'user_trip_send' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'user_flights_send' => true,
			//'treasurer_trip_edit' => true,
		],
		4 => [
			'user_trip_update' => true,
			'user_trip_send' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'user_expenses_send' => true,
			'treasurer_trip_edit' => true,
		],
		5 => [
			'user_trip_update' => true,
			'user_trip_delete' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'manager_trip_send' => true,
			'treasurer_trip_edit' => true,
		],
		6 => [
			'user_trip_update' => true,
			'user_trip_send' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'user_expenses_send' => true,
			'treasurer_trip_edit' => true,
		],
		7 => [],
		8 => [
			'treasurer_trip_send' => true,
			'treasurer_trip_edit' => true,
		],
		9 => [
			'user_trip_update' => true,
			'user_trip_send' => true,
			'user_expenses_create' => true,
			'user_expenses_update' => true,
			'user_expenses_delete' => true,
			'user_expenses_send' => true,
			'user_flights_create' => true,
			'user_flights_update' => true,
			'user_flights_delete' => true,
			'treasurer_trip_edit' => true,
		],
		10 => [
			'treasurer_trip_edit' => true,
			'treasurer_trip_export' => true,
		],
		11 => [
			//'treasurer_trip_edit' => true,
			'treasurer_trip_export' => true,
		],
		12 => [
			'user_trip_update' => true,
			//'treasurer_trip_edit' => true,
		],
	];

	protected $status_change = [

	];

	public $names = [
		0 => 'Add flights and send for review',
		1 => 'You can send for review',
		2 => 'Business trip and flights are awaiting verification',
		3 => 'Business trip and flights are not confirmed',
		4 => 'Check travel and flight passed. Add expenses and send them for review.',
		5 => 'Manager checks expenses',
		6 => 'Expenses are not confirmed',
		7 => '-',
		8 => 'A business trip is checked by an accountant',
		9 => 'The accountant did not confirm the trip',
		10 => 'Is closed',
		11 => 'In the archive',
	];

	public $names_filter = [
		0 => 'New',
		1 => 'Confirmed (trip)',
		12 => 'Rejected (trip)',
		2 => 'Wait for flight appr',
		3 => 'Rejected (flight)',
		4 => 'Approved (flight)',
		5 => 'Wait for mng. (exp)',
		6 => 'Rejected (exp)',
		// 7 => 'Approved (exp)',
		8 => 'Wait for trs',
		9 => 'Rejected (trs)',
		10 => 'Approved (trs)',
		11 => 'Archived',
	];

	public $names_filter_manager = [
		-1 => 'Wait for me',
		0 => 'New',
		1 => 'Confirmed (trip)',
		12 => 'Rejected (trip)',
		2 => 'Wait for flight appr',
		3 => 'Rejected (flight)',
		4 => 'Approved (flight)',
		5 => 'Wait for mng. (exp)',
		6 => 'Rejected (exp)',
		// 7 => 'Approved (exp)',
		8 => 'Wait for trs',
		9 => 'Rejected (trs)',
		10 => 'Approved (trs)',
		11 => 'Archived',
	];

	public $names_filter_treasurer = [
		/*
		0 => 'New',
		1 => 'Confirmed (trip)',
		12 => 'Rejected (trip)',
		2 => 'Wait for flight appr',
		3 => 'Rejected (flight)',
		4 => 'Approved (flight)',
		5 => 'Wait for mng. (exp)',
		6 => 'Rejected (exp)',
		*/
		// 7 => 'Approved (exp)',
		8 => 'Wait for trs',
		9 => 'Rejected (trs)',
		10 => 'Approved (trs)',
		11 => 'Archived',
	];

	public $names_filter_treasurer_all = [
		0 => 'New',
		1 => 'Confirmed (trip)',
		12 => 'Rejected (trip)',
		2 => 'Wait for flight appr',
		3 => 'Rejected (flight)',
		4 => 'Approved (flight)',
		5 => 'Wait for mng. (exp)',
		6 => 'Rejected (exp)',
		// 7 => 'Approved (exp)',
		8 => 'Wait for trs',
		9 => 'Rejected (trs)',
		10 => 'Approved (trs)',
		11 => 'Archived',
	];

	public $names_short = [
		0 => 'New',
		1 => 'Confirmed (trip)',
		12 => 'Rejected (trip)',
		2 => 'Wait for flight appr',
		3 => 'Rejected (flight)',
		4 => 'Approved (flight)',
		5 => 'Wait for mng. (exp)',
		6 => 'Rejected (exp)',
		// 7 => 'Approved (exp)',
		8 => 'Wait for trs',
		9 => 'Rejected (trs)',
		10 => 'Approved (trs)',
		11 => 'Archived',
	];

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%trips}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if ($this->isNewRecord) {
				$this->created_at = new Expression('NOW()');
			}
			$dirtyAttributes = $this->getDirtyAttributes();
			if (isset($dirtyAttributes['departure_date']) || isset($dirtyAttributes['arrival_date'])) {
				if ($this->status == 12) {
					$this->status = 0;
					EmailHelper::send('worker.manager.trip-new', $this);
				}
			}
			if (isset($dirtyAttributes['trips_types_id'])) {
				Expenses::updateAll(['trips_types_id' => $dirtyAttributes['trips_types_id']], ['trips_id' => $this->id]);
				Flights::updateAll(['trips_types_id' => $dirtyAttributes['trips_types_id']], ['trips_id' => $this->id]);

			}
			$this->updated_at = new Expression('NOW()');
			return true;
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function afterSave($insert, $changedAttributes) {
			if ($insert) {
				EmailHelper::send('worker.manager.trip-new', $this);
			} else {
				if ($this->after_save) {
					$this->generateEshel(true);
				}
			}
			parent::afterSave($insert, $changedAttributes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios[self::SCENARIO_NEW] = ['trips_types_id', 'countries_id', 'departure_date', 'arrival_date', 'city', 'user_note', 'plant'];
		$scenarios[self::SCENARIO_SAVE] = ['trips_types_id', 'countries_id', 'departure_date', 'arrival_date', 'city', 'user_note', 'plant'];
		$scenarios[self::SCENARIO_SEARCH] = ['trips_types_id', 'countries_id', 'departure_date', 'arrival_date'];
		$scenarios[self::SCENARIO_MANAGER_SEND] = ['checker_note', 'manager_type'];
		$scenarios[self::SCENARIO_UPLOAD] = ['filesList'];
		return $scenarios;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			/*
			[['active', 'status', 'users_id', 'trips_types_id', 'countries_id', 'plant'], 'integer'],
			[['total_nis'], 'number'],
			[['departure_date', 'arrival_date'], 'safe'],
			[['city', 'user_note', 'checker_note'], 'string', 'max' => 255],
			*/

			[['city', 'user_note', 'checker_note'], 'string', 'max' => 255, 'on' => [self::SCENARIO_NEW, self::SCENARIO_SAVE]],
			[['trips_types_id', 'countries_id', 'plant', 'city', 'departure_date', 'arrival_date'], 'required', 'on' => [self::SCENARIO_NEW, self::SCENARIO_SAVE]],
			['plant', 'in', 'range' => [10, 11], 'on' => [self::SCENARIO_NEW, self::SCENARIO_SAVE]],
			['departure_date', 'compare', 'compareAttribute' => 'arrival_date', 'operator' => '<=', 'enableClientValidation' => false, 'on' => [self::SCENARIO_NEW, self::SCENARIO_SAVE]],

			[['trips_types_id', 'countries_id', 'departure_date', 'arrival_date'], 'safe', 'on' => self::SCENARIO_SEARCH],

			['manager_type', 'in', 'range' => [0, 1], 'on' => self::SCENARIO_MANAGER_SEND],
			//['checker_note', 'required', 'on' => self::SCENARIO_MANAGER_SEND],
			['checker_note', 'string', 'max' => 255, 'on' => self::SCENARIO_MANAGER_SEND],
			['checker_note', 'safe', 'on' => self::SCENARIO_MANAGER_SEND],
			['checker_note', 'validateCheckerNote', 'skipOnEmpty' => false, 'skipOnError' => false, 'on' => self::SCENARIO_MANAGER_SEND],

			[['filesList'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, bmp, pdf, xls, xlsx, doc, docx, ppt, pptx, txt, csv, zip, 7z, rar', 'maxFiles' => 16,/*'checkExtensionByMimeType' => false,*/ 'on' => [self::SCENARIO_SAVE, self::SCENARIO_UPLOAD]],

		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateCheckerNote($attribute, $params, $validator)
	{
		if ($this->status > 0 && $this->manager_type != 1 && empty($this->checker_note)) {
			$this->addError($attribute, 'Checker note cannot be blank.');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('app', 'Trip ID'),
			'active' => Yii::t('app', 'Active'),
			'status' => Yii::t('app', 'Trip Status'),
			'users_id' => Yii::t('app', 'Employee'),
			'trips_types_id' => Yii::t('app', 'Trip Purpose'),
			'countries_id' => Yii::t('app', 'Country'),
			'total_nis' => Yii::t('app', 'NIS'),
			'plant' => Yii::t('app', 'Company'),
			'city' => Yii::t('app', 'City'),
			'user_note' => Yii::t('app', 'Notes'),
			'checker_note' => Yii::t('app', 'Checker Note'),
			'departure_date' => Yii::t('app', 'Departure/Arrival Dates'),
			'arrival_date' => Yii::t('app', 'Arrival Date'),
			'created_at' => Yii::t('app', 'Created At'),
			'updated_at' => Yii::t('app', 'Updated At'),
			'filter_date_range' => Yii::t('app', 'Departure/Arrival Dates'),
			'filter_date_range_departure' => Yii::t('app', 'Departure'),
			'filter_date_range_arrival' => Yii::t('app', 'Arrival'),

			'days' => Yii::t('app', 'Days'),
			'filesList' => Yii::t('app', 'Files'),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function isAllow($rule) {
		return (!empty($this->rules_of_change[$this->status][$rule])) ? true : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function send($role = 'user') {
		if (!$this->canSend($role)) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		$user = $this->user;
		switch ($this->status) {
			case 0:
			case 1:
				
				if ($this->status == 0) {
					$this->status = 2;
				} else {
					$this->status += 1;
				}
				
				if (!$user->need_manager_check) {
					$this->status = 4;
					$this->generateEshel(true);
					EmailHelper::send('worker.treasurer.trip-request', $this);
					break;
				}
				EmailHelper::send('worker.manager.trip-request', $this);
				EmailHelper::send('worker.treasurer.trip-request', $this);
				break;
			
			case 3:
				$this->status -= 1;
				EmailHelper::send('worker.manager.trip-request', $this);
				break;
			
			case 4:
				$this->status += 1;
				if (!$user->need_manager_check) {
					$this->status = 8;
					EmailHelper::send('manager.treasurer.trip-accept', $this);
					break;
				}
				EmailHelper::send('worker.manager.expenses-request', $this);
				break;
			
			case 6:
				$this->status -= 1;
				EmailHelper::send('worker.manager.expenses-request', $this);
				break;
			
			case 9:
				$this->status -= 1;
				EmailHelper::send('manager.treasurer.trip-accept', $this);
				$this->generateEshel(true);
				break;

		}
		$rows = self::updateAll(['status' => $this->status], 'id = :id', [':id' => $this->id]);
		return $rows;

	}

	/**
	 * {@inheritdoc}
	 */
	public function sendManager($type = 0) {
		if (!$this->canSend('manager')) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if ($this->status == 0) {
			Yii::trace($type, 'status123');
			if ($type == 1) {
				$this->status = 1;
				EmailHelper::send('manager.worker.trip-start', $this);
			} else {
				$this->status = 12;
				EmailHelper::send('manager.worker.trip-reject', $this);	
			}	
		} elseif ($this->status == 2) {
			if ($type == 0) {
				$this->status = 3;
				EmailHelper::send('manager.worker.trip-reject', $this);
			} elseif($type == 1) {
				$this->status = 4;
				EmailHelper::send('manager.worker.trip-accept', $this);
				EmailHelper::send('manager.treasurer.trip-accept', $this);
				if ($this->flights) {
					EmailHelper::send('treasurer.custom.expenses-accept', $this);
				}
			}
		} elseif ($this->status == 5) {
			if ($type == 0) {
				$this->status = 6;
				EmailHelper::send('manager.worker.expenses-reject', $this);
			} elseif($type == 1) {
				$this->status = 8;
				EmailHelper::send('manager.worker.expenses-accept', $this);
				EmailHelper::send('manager.treasurer.expenses-accept', $this);
				$this->generateEshel(true);
			}
		}
		$rows = self::updateAll(['status' => $this->status], 'id = :id', [':id' => $this->id]);
		return $rows;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sendTreasurer($type = 0) {
		if (!in_array($this->status, [8, 9, 10, 11])) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if ($this->status == 8) {
			if ($type == 0) {
				$this->status = 9;
				EmailHelper::send('treasurer.worker.expenses-reject', $this);
			} elseif($type == 1) {
				$this->status = 10;
				if ($this->after_save) {
					Expenses::updateAll(['status' => 1], ['trips_id' => $this->id]);
				}
			}
		} elseif ($this->status == 10 || $this->status == 11) {
			$export = @file_get_contents(Yii::$app->params['export_to_m3_url'].$this->id);
			Yii::trace($export, 'Export to M3 response');
			if ($this->status == 10) {
				if ($this->total_nis >= 0) {
					EmailHelper::send('treasurer.worker.expenses-accept-refunded', $this);
				} else {
					EmailHelper::send('treasurer.worker.expenses-accept-deducted', $this);
				}
				EmailHelper::send('treasurer.custom.expenses-accept', $this);
			}
			$this->status = 11;
		}
		$rows = self::updateAll(['status' => $this->status], 'id = :id', [':id' => $this->id]);
		return $rows;
	}

	/**
	 * {@inheritdoc}
	 */
	public function canSend($role = 'user') {
		return $this->isAllow($role.'_trip_send');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canSendFlights($role = 'user') {
		return $this->isAllow($role.'_flights_send');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canSendExpenses($role = 'user') {
		return $this->isAllow($role.'_expenses_send');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canUpdate($role = 'user') {
		return $this->isAllow($role.'_trip_update');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canDelete($role = 'user') {
		return $this->isAllow($role.'_trip_delete');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canAddFlights($role = 'user') {
		return $this->isAllow($role.'_flights_create');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canUpdateFlights($role = 'user') {
		return $this->isAllow($role.'_flights_update');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canDeleteFlights($role = 'user') {
		return $this->isAllow($role.'_flights_delete');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canAddExpenses($role = 'user') {
		return $this->isAllow($role.'_expenses_create');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canUpdateExpenses($role = 'user') {
		return $this->isAllow($role.'_expenses_update');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canDeleteExpenses($role = 'user') {
		return $this->isAllow($role.'_expenses_delete');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatusName() {
		return $this->names[$this->status];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatusShort() {
		return $this->names_short[$this->status];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStatusList() {
		return $this->names_short[$this->status];
	}


	/**
	 * {@inheritdoc}
	 */
	public function generateEshel($keep_vacations = false)
	{
		
		$vd = Expenses::find()
			->where(['trips_id'=>$this->id, 'is_sys'=>1])
			->asArray()->all();
		$vacations = ArrayHelper::index($vd, 'spending_date');
		Yii::trace($keep_vacations, 'generateEshel: keep vacations');
		Yii::trace($vacations, 'generateEshel: $vacations'); 
		Yii::trace($vd, 'generateEshel: $vd'); 
		Expenses::deleteAll([
			'AND', 'users_id = :user_id', 'trips_id = :trips_id', [
				'IN', 'expenses_types_id',
				[1, 2, 3, 4]
			]
			], [
			':user_id' => $this->users_id,
			':trips_id' => $this->id,
		]);
		$flights = Flights::find()
			->where(['trips_id' => $this->id])
			->orderBy('departure_date, departure_time ASC')
			->asArray()
			->all();
		if (is_array($flights) && count($flights) > 0) {
			$flights_array_all = ArrayHelper::index($flights, null, 'departure_date');
			$flights_array = ArrayHelper::index($flights, 'departure_date');
			$now = new \DateTime();
			$departure_time =  \DateTime::createFromFormat('Y-m-d H:i:s', $this->departure_date . '00:00:00');
			$arrival_time = \DateTime::createFromFormat('Y-m-d H:i:s', $this->arrival_date . '00:00:00');
			$interval = $departure_time->diff($arrival_time);
			$days = $interval->days;
			$spending_date = $departure_time;
			$spending_date_first = $departure_time->format('Y-m-d');

			foreach ($flights as $flight) {
				if ($flight['from_country'] == 123) {
					$departure_time =  \DateTime::createFromFormat('Y-m-d H:i:s', $flight['departure_date'] . '00:00:00');
					$spending_date = $departure_time;
					$spending_date_first = $departure_time->format('Y-m-d');
					$interval = $departure_time->diff($arrival_time);
					$days = $interval->days;
					break;
				}
			}

			$countries_id = $this->countries_id;
			$amount = $this->country->rate_day;
			$rate_half_day = $this->country->rate_half_day;
			$rate_night = $this->country->rate_night;
			$departure_hour_from_home = false; 
			$departure_hour_to_home = false; 
			$flight_first = true;
			$flight_first_date = false;
			$flight_last_date = false;
			$flight_last = array_pop($flights_array);
			$flights_array[$flight_last['departure_date']] = $flight_last;
			$to_home = false;
			$sys_expenses = [];
			$is_night = 0;
			$ex_status = 1;
			$trips_types_id = $this->trips_types_id;
			for ($i=0; $i <= $days; $i++) {
				$spending_date_str = $spending_date->format('Y-m-d');
				$sys_expenses[$spending_date_str] = [
					'status' => 0,
					'is_sys' => 1,
					'trips_id' => NULL,
					'users_id' => NULL,
					'countries_id' => NULL,
					'expenses_types_id' => NULL,
					'trips_types_id' => NULL,
					'payments_methods_id' => NULL, // N/A
					'currency_codes_id' => NULL,
					'amount' => NULL,
					'total_nis' => NULL,
					'exchange_rate' => NULL,
					'exchange_rate_date' => NULL,
					'receipt_number' => NULL,
					'user_note' => NULL,
					'treasurer_note' => NULL,
					'spending_date' => NULL,
					'created_at' => NULL,
					'updated_at' => NULL,
				];

				$rate_night = 0;
				$is_night = 0;
				$expenses_types_id = 2; // default: day eshel

				if (isset($flights_array_all[$spending_date_str])) {
					foreach ($flights_array_all[$spending_date_str] as $departure_date => $flight) {
						$expenses_types_id = 3; // eshel day flight if flight exist in this day
						$country = Countries::findOne($flight['to_country']);
						$countries_id = $country->id;
						$amount = $country->rate_day;
						$rate_half_day = 0;
						if ($flight['is_night_trip'] == '1') {
							$expenses_types_id = 4; // eshel night flight
							$rate_night += $country->rate_night;
						}
						$sys_expenses[$spending_date_str]['user_note'] .= 'rate_night: +$'.$rate_night.'; ';
						if ($flight_first && $flight['from_country'] == 123) {
							$departure_hour_from_home = intval($flight['departure_time']);
							$flight_first_date = $flight['departure_date'];
							$spending_date_first = $flight['departure_date'];
							$flight_first = false;
						} elseif($flight['to_country'] == 123) {
							$country = Countries::findOne($flight['from_country']);
							$countries_id = $country->id;
							$amount = $country->rate_day;
							$departure_hour_to_home = intval($flight['departure_time']);
							$flight_last_date = $flight['departure_date'];
							$rate_half_day = $country->rate_half_day;
							$to_home = $spending_date_str;
						}
					}

					if ($flight_last_date && !is_bool($departure_hour_from_home) && !is_bool($departure_hour_to_home)) {
						$country_first = Countries::findOne($sys_expenses[$flight_first_date]['countries_id']);
						if ($departure_hour_from_home >= 13) {
							$sys_expenses[$flight_first_date]['amount'] = 26;
							if ($flights_array[$flight_first_date]['is_night_trip']) {
								$sys_expenses[$flight_first_date]['amount'] += $country_first->rate_night;
							}
							$sys_expenses[$flight_first_date]['total_nis'] = $sys_expenses[$flight_first_date]['amount'] * $sys_expenses[$flight_first_date]['exchange_rate'];
						} else {
							$sys_expenses[$flight_first_date]['amount'] = $country_first->rate_day;
							if ($flights_array[$flight_first_date]['trips_types_id'] == 1) {
								$sys_expenses[$flight_first_date]['amount'] = Yii::$app->params['exhibition_rate'];
							}
							if ($flights_array[$flight_first_date]['is_night_trip']) {
								$sys_expenses[$flight_first_date]['amount'] += $country_first->rate_night;
							}
							$sys_expenses[$flight_first_date]['total_nis'] = $sys_expenses[$flight_first_date]['amount'] * $sys_expenses[$flight_first_date]['exchange_rate'];
						}


						if ($departure_hour_to_home <= 13) {
							$amount = 26;
							if ($flights_array[$spending_date_str]['is_night_trip']) {
								$amount += $country->rate_night;
							}
						} else {
							$amount = $country->rate_day;
							if ($flights_array[$spending_date_str]['trips_types_id'] == 1) {
								$amount = Yii::$app->params['exhibition_rate'];
							}
							if ($flights_array[$spending_date_str]['is_night_trip']) {
								$amount += $country->rate_night;
							}
						}
						$flight_last_date = false;
					} else {
						$sys_expenses[$spending_date_str]['user_note'] .= 'flight (from '.$departure_hour_from_home.' to '.$departure_hour_to_home.'); ';
						$sys_expenses[$spending_date_str]['user_note'] .= 'total eshel (rate_day '.$amount.' + rate_night '.$rate_night.'); ';
						$amount = $amount + $rate_night;
					}
				} else {
					$country = Countries::findOne($countries_id);
					$amount = $country->rate_day;
					$sys_expenses[$spending_date_str]['user_note'] .= 'total eshel normal day: +$'.$amount.'; ';
				}
				$rate = CurrencyHelper::api('USD', Yii::$app->formatter->asDate($spending_date_first, 'php:Ymd'));
				if (!$rate) {
					$rate = 0;
				}
				if ($keep_vacations && !empty($vacations[$spending_date_str]['trips_types_id'])) {
						$trips_types_id = $vacations[$spending_date_str]['trips_types_id'];
				}
				if (($expenses_types_id != 3 && $expenses_types_id != 4)) {
					if ($trips_types_id == 1) {
						$amount = Yii::$app->params['exhibition_rate'];
					}
					if (!empty($vacations[$spending_date_str]['is_night']) && $vacations[$spending_date_str]['is_night'] == 1) {
						$is_night = 1;
						$amount += $country->rate_night;
					}
					if (!empty($vacations[$spending_date_str]['expenses_types_id']) && $vacations[$spending_date_str]['expenses_types_id'] == 1) {
						$expenses_types_id = 1;
					}
				}
				if (!empty($vacations[$spending_date_str]['status'])) {
						$ex_status = $vacations[$spending_date_str]['status'];
				}
				$ex_total_nis = $amount * $rate;
				$sys_expenses[$spending_date_str] = [
					'status' => $ex_status,
					'is_sys' => 1,
					'is_night' => $is_night,
					'trips_id' => $this->id,
					'users_id' => $this->users_id,
					'countries_id' => $countries_id,
					'expenses_types_id' => $expenses_types_id, 
					'trips_types_id' => $trips_types_id,
					'payments_methods_id' => 1, // N/A
					'currency_codes_id' => 2,
					'amount' => $amount,
					'total_nis' => $ex_total_nis,
					'exchange_rate' => $rate,
					'exchange_rate_date' => $spending_date_first,
					'receipt_number' => '',
					'user_note' => '',
					'treasurer_note' => '',
					'spending_date' => $spending_date_str,
					'created_at' => $now->format('Y-m-d H:i:s'),
					'updated_at' => $now->format('Y-m-d H:i:s'),
				];
				$spending_date->add(new \DateInterval('P1D'));
				if ($to_home == $spending_date_str) {
					break;
				}
			}
			$attributes = [
					//'id',
					'status',
					'is_sys',
					'is_night',
					'trips_id',
					'users_id',
					'countries_id',
					'expenses_types_id', 
					'trips_types_id', 
					'payments_methods_id',
					'currency_codes_id',
					'amount',
					'total_nis',
					'exchange_rate',
					'exchange_rate_date',
					'receipt_number',
					'user_note',
					'treasurer_note',
					'spending_date',
					'created_at',
					'updated_at',
			];
			$expenses = Yii::$app->db->createCommand()->batchInsert(Expenses::tableName(), $attributes, $sys_expenses)->execute();
		}
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function days() {
		$departure_date = new \DateTime($this->departure_date);
		$arrival_date = new \DateTime($this->arrival_date);
		$interval = $departure_date->diff($arrival_date);
		if ($interval->days) {
			return $interval->days;
		} else {
			return 1;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function totalRefundSet($total_nis)
	{
		if ($this->total_nis != $total_nis) {
			$this->total_nis = $total_nis;
			$this->after_save = false;
			$this->update(false, ['total_nis']);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function uploadFilesList()
	{
		$folder = Yii::getAlias('@app/files/uploaded/' . $this->users_id . '/trips/' . $this->id . '/');
		if ($this->filesList) {
			if (!file_exists($folder)) {
				FileHelper::createDirectory($folder);
			}
			foreach ($this->filesList as $file) {
				$path = $file->baseName . '.' . $file->extension;
				if($file->saveAs($folder . 'tmp'))
				{
					$modelFileList = new FilesList();
					$modelFileList->active = 1;
					$modelFileList->trips_id = $this->id;
					$modelFileList->users_id = $this->users_id;
					$modelFileList->path = $path;
					if($modelFileList->save())
					{
						if (!rename($folder . 'tmp', $folder . $modelFileList->id)) {
							$modelFileList->delete();
						}
					} else {
						$modelFileList->delete();
					}
				}
			}
		}
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFlights()
	{
		return $this->hasMany(Flights::className(), ['trips_id' => 'id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExpenses()
	{
		return $this->hasMany(Expenses::className(), ['trips_id' => 'id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFirstAmounts()
	{
		return $this->hasMany(FirstAmounts::className(), ['trips_id' => 'id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTripsType()
	{
		return $this->hasOne(TripsTypes::className(), ['id' => 'trips_types_id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCountry()
	{
		return $this->hasOne(Countries::className(), ['id' => 'countries_id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUser()
	{
		return $this->hasOne(Users::className(), ['id' => 'users_id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFiles()
	{
		return $this->hasMany(FilesList::className(), ['trips_id' => 'id']);
	}

	/**
	 * {@inheritdoc}
	 * @return TripsQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new TripsQuery(get_called_class());
	}
}
