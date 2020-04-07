<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\base\UserException;
use yii\data\ActiveDataProvider;
use app\helpers\DynamicModel;
use yii\helpers\ArrayHelper;

use app\models\Users;
use kartik\mpdf\Pdf;

use app\models\Trips;
use app\models\TripsSearch;
use app\models\TripsTypes;
use app\models\FirstAmounts;
use app\models\CurrencyCodes;
use app\models\Countries;
use app\models\Flights;
use app\models\Expenses;
use app\models\ExpensesTypes;
use app\models\PaymentsMethods;
use app\models\FilesList;

class TripsController extends Controller
{
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [

			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['tripsManagment'],
					],
				],
			],

		];
	}

	public function actionIndex()
	{
		$user_id = Yii::$app->user->id;
		$searchModel = new TripsSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams, $user_id);
		$trips_types = TripsTypes::listByUserId($user_id);
		$countries = Countries::listByUserId($user_id);
		return $this->render('index', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'trips_types' => $trips_types,
			'countries' => $countries,
		]);
	}

	public function actionSend($id) {
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelTrips->send();
		return $this->redirect(['view', 'id' => $modelTrips->id]);
	}

	public function actionView($id)
	{
		$user_id = Yii::$app->user->id;
		$user_plant = Yii::$app->user->identity->plant;
		$modelTrips = Trips::find()->oneByIdAndUserId($id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}

		$modelFlights = Flights::find()->where(['trips_id' => $modelTrips->id])->andWhere(['users_id' => $user_id]);
		$modelExpenses = Expenses::find()->where(['trips_id' => $modelTrips->id])->andWhere(['users_id' => $user_id]);

		$modelTrips->scenario = Trips::SCENARIO_UPLOAD;

		if (Yii::$app->request->isPost && $modelTrips->load(Yii::$app->request->post())) {
			$modelTrips->filesList = UploadedFile::getInstances($modelTrips, 'filesList');
			if ($modelTrips->validate()) {
				$modelTrips->uploadFilesList();
				return $this->redirect(['view', 'id'=>$modelTrips->id]);
			}
		}

		$flightsDataProvider = new ActiveDataProvider([
			'query' => $modelFlights,
			'pagination' => [
				'pageSize' => 15,
			],
			'sort' => [
				'defaultOrder' => [
					'departure_date' => SORT_ASC,
					'departure_time' => SORT_ASC, 
				]
			],
		]);

		$expensesDataProvider = new ActiveDataProvider([
			'query' => $modelExpenses,
			'pagination' => [
				'pageSize' => 50,
			],
			'sort' => [
				'defaultOrder' => [
					'spending_date' => SORT_ASC,
					'is_sys' => SORT_DESC,
				]
			],
		]);

		$currency_codes = CurrencyCodes::listAll();
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$expenses_types = ExpensesTypes::listAll();
		$payments_methods = PaymentsMethods::listAll();
		return $this->render('view', [
			'user_plant' => $user_plant,
			'currency_codes' => $currency_codes,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'flightsDataProvider' => $flightsDataProvider,
			'expensesDataProvider' => $expensesDataProvider,
		]);
	}

	public function actionNew()
	{
		$modelTrips = new Trips(['scenario' => Trips::SCENARIO_NEW]);
		$modelsFirstAmounts = [new FirstAmounts()];
		$user_plant = Yii::$app->user->identity->plant;

		$date_now = (new \DateTime())->format('Y-m-d');
		$modelTrips->departure_date = $date_now;
		$modelTrips->arrival_date = $date_now;

		if (in_array($user_plant, [10, 11])) {
			$modelTrips->plant = $user_plant;
		} else {
			$modelTrips->plant = $user_plant/10;
		}

		if ($modelTrips->load(Yii::$app->request->post())) {
			$modelsFirstAmounts = DynamicModel::createMultiple(FirstAmounts::classname());
			DynamicModel::loadMultiple($modelsFirstAmounts, Yii::$app->request->post());

			// validate all models
			$valid = $modelTrips->validate();
			$valid = DynamicModel::validateMultiple($modelsFirstAmounts) && $valid;

			if ($valid) {
				
				$transaction = \Yii::$app->db->beginTransaction();

				$modelTrips->active = Trips::STATUS_ACTIVE;
				$modelTrips->users_id = Yii::$app->user->id;
				$modelTrips->status = 0;

				try {
					if ($flag = $modelTrips->save(false)) {
						foreach ($modelsFirstAmounts as $modelFirstAmounts) {
							if (empty($modelFirstAmounts->first_amount) || empty($modelFirstAmounts->currency_codes_id)) {
								continue;
							}
							$modelFirstAmounts->trips_id = $modelTrips->id;
							$modelFirstAmounts->users_id = Yii::$app->user->id;
							if (! ($flag = $modelFirstAmounts->save(false))) {
								$transaction->rollBack();
								break;
							}
						}
					}

					if ($flag) {
						$transaction->commit();
						return $this->redirect(['index', 'id' => $modelTrips->id]);
					}
				} catch (Exception $e) {
					$transaction->rollBack();
				}
			}
		}
		$currency_codes = CurrencyCodes::listAll();
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		return $this->render('_form', [
			'user_plant' => $user_plant,
			'currency_codes' => $currency_codes,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'modelTrips' => $modelTrips,
			'modelsFirstAmounts' => (empty($modelsFirstAmounts)) ? [new FirstAmounts] : $modelsFirstAmounts
		]);
	}

	public function actionDelete($id)
	{
		$modelTrips = Trips::findOne($id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		if ($modelTrips->users_id != Yii::$app->user->id) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if ($modelTrips->canDelete()) {
			$modelTrips->active = 0;
			$modelTrips->save(false);
		}
		return $this->redirect(['index']);
	}

	public function actionFlightsDelete($trip_id, $flight_id)
	{
		$modelTrips = Trips::findOne($trip_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelFlights = Flights::findOne($flight_id);
		if (!$modelFlights) {
			throw new NotFoundHttpException('Page not found');
		}
		if ($modelTrips->users_id != Yii::$app->user->id || $modelFlights->users_id != Yii::$app->user->id) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if ($modelTrips->isAllow('user_flights_delete')) {
			$modelFlights->delete();
			$modelTrips->generateEshel();
		}
		return $this->redirect(['view', 'id'=>$trip_id]);
	}

	public function actionExpensesDelete($trip_id, $expense_id)
	{
		$modelTrips = Trips::findOne($trip_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelExpenses = Expenses::findOne($expense_id);
		if (!$modelExpenses) {
			throw new NotFoundHttpException('Page not found');
		}
		if ($modelTrips->users_id != Yii::$app->user->id || $modelExpenses->users_id != Yii::$app->user->id) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if ($modelTrips->isAllow('user_expenses_delete')) {
			$modelExpenses->delete();
			$modelTrips->generateEshel();
		}
		return $this->redirect(['view', 'id'=>$trip_id]);
	}

	public function actionFileDelete($id, $trip = 0)
	{
		$modelFilesList = FilesList::findOne($id);
		if (!$modelFilesList) {
			throw new NotFoundHttpException('Page not found');
		}
		$trip_id = $modelFilesList->trips_id;
		if ($modelFilesList->users_id != Yii::$app->user->id ) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if(@unlink($modelFilesList->filepath()))
		{
			$modelFilesList->delete();
		}
		if ($trip > 0) {
			return $this->redirect(['view', 'id'=>$trip]);
		}
		return $this->redirect(['view', 'id'=>$trip_id]);
	}

	public function actionFileDelete2($id, $trip = 0, $expense = 0)
	{
		$modelFilesList = FilesList::findOne($id);
		if (!$modelFilesList) {
			throw new NotFoundHttpException('Page not found');
		}
		$trip_id = $modelFilesList->trips_id;
		if ($modelFilesList->users_id != Yii::$app->user->id ) {
			throw new ForbiddenHttpException('You are not authorized to do this.');
		}
		if(@unlink($modelFilesList->filepath()))
		{
			$modelFilesList->delete();
		}
		if ($trip > 0) {
			return $this->redirect(['trips/expenses-update-files', 'trip_id' => $trip, 'expense_id' => $expense]);
		}
		return $this->redirect(['view', 'id'=>$trip_id]);
	}

	public function actionUpdate($id)
	{
		$modelTrips = Trips::findOne($id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelTrips->scenario = Trips::SCENARIO_SAVE;
		$modelsFirstAmounts = $modelTrips->firstAmounts;
		$modelTrips->filesList = UploadedFile::getInstances($modelTrips, 'filesList');

		if ($modelTrips->load(Yii::$app->request->post())) {
			$oldIDs = ArrayHelper::map($modelsFirstAmounts, 'id', 'id');
			$modelsFirstAmounts = DynamicModel::createMultiple(FirstAmounts::classname(), $modelsFirstAmounts);
			DynamicModel::loadMultiple($modelsFirstAmounts, Yii::$app->request->post());
			$deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelsFirstAmounts, 'id', 'id')));

			// validate all models
			$valid = $modelTrips->validate();
			$valid = DynamicModel::validateMultiple($modelsFirstAmounts) && $valid;

			if ($valid) {

				$modelTrips->uploadFilesList();
				
				$transaction = \Yii::$app->db->beginTransaction();

				try {
					if ($flag = $modelTrips->save(false)) {
						if (!empty($deletedIDs)) {
							FirstAmounts::deleteAll(['id' => $deletedIDs]);
						}
						foreach ($modelsFirstAmounts as $modelFirstAmounts) {
							if (empty($modelFirstAmounts->first_amount) || empty($modelFirstAmounts->currency_codes_id)) {
								continue;
							}
							$modelFirstAmounts->trips_id = $modelTrips->id;
							$modelFirstAmounts->users_id = Yii::$app->user->id;
							if (! ($flag = $modelFirstAmounts->save(false))) {
								$transaction->rollBack();
								break;
							}
						}
					}

					if ($flag) {
						$transaction->commit();
						return $this->redirect(['view', 'id' => $modelTrips->id]);
					}
				} catch (Exception $e) {
					$transaction->rollBack();
				}
				$modelTrips->generateEshel();
			}
		}
		$currency_codes = CurrencyCodes::listAll();
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$files = $modelTrips->files;
		return $this->render('_form', [
			'user_plant' => false,
			'currency_codes' => $currency_codes,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'modelTrips' => $modelTrips,
			'modelsFirstAmounts' => (empty($modelsFirstAmounts)) ? [new FirstAmounts] : $modelsFirstAmounts,
			'files' => $files,
		]);
	}

	public function actionFlightsNew($trip_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelFlights = new Flights(['scenario' => Flights::SCENARIO_NEW]);
		$modelFlights->trips_types_id = $modelTrips->trips_types_id;
		$flights_count = Flights::find()->where(['trips_id' => $modelTrips->id])->count();
		if ($flights_count == 0) {
			$modelFlights->from_country = 123;
			$modelFlights->to_country = $modelTrips->countries_id;
			$modelFlights->from_city = 'Tel Aviv';
			$modelFlights->to_city = $modelTrips->city;
			$modelFlights->departure_date = $modelTrips->departure_date;
		} elseif($flights_count == 1) {
			$flights_last = $flights_count = Flights::find()->where(['trips_id' => $modelTrips->id])->orderBy('id DESC')->one();
			$modelFlights->from_country = $flights_last->to_country;
			$modelFlights->to_country = 123;
			$modelFlights->from_city = $modelTrips->city;
			$modelFlights->to_city = 'Tel Aviv';
			$modelFlights->departure_date = $modelTrips->arrival_date;
		} else {
			$flights_last = $flights_count = Flights::find()->where(['trips_id' => $modelTrips->id])->orderBy('id DESC')->one();
			$modelFlights->from_country = $flights_last->to_country;
			$modelFlights->from_city = $flights_last->to_city;
			$modelFlights->departure_date = $modelTrips->arrival_date;
		}
		$modelFlights->departure_time = '00:00:00';
		$modelFlights->trips_id = $modelTrips->id;
		$modelFlights->users_id = $user_id;
		if ($modelFlights->load(Yii::$app->request->post())) {
			if ($modelFlights->validate()) {
				if ($modelFlights->save()) {
					$modelTrips->flightChanges();
					$modelTrips->generateEshel();
					return $this->redirect(['view', 'id' => $modelTrips->id]);
				}
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		return $this->render('_form_flights', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'modelTrips' => $modelTrips,
			'modelFlights' => $modelFlights,
		]);
	}

	public function actionFlightsUpdate($trip_id, $flight_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelFlights = Flights::find()
			->where(['id' => $flight_id])
			->andWhere(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->limit(1)
			->one();
		if (!$modelFlights) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelFlights->scenario = Flights::SCENARIO_NEW;
		if ($modelFlights->load(Yii::$app->request->post())) {
			if ($modelFlights->validate()) {
				if ($modelFlights->save()) {
					$modelTrips->flightChanges();
					$modelTrips->generateEshel();
					return $this->redirect(['view', 'id' => $modelTrips->id]);
				}
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		return $this->render('_form_flights', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'modelTrips' => $modelTrips,
			'modelFlights' => $modelFlights,
		]);
	}

	public function actionExpensesNew($trip_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelExpenses = new Expenses(['scenario' => Expenses::SCENARIO_NEW]);
		$country = $modelTrips->country;
		if ($country && $country->cc_id) {
			$modelExpenses->currency_codes_id = $country->cc_id;
		}
		$modelExpenses->status = 0;
		$modelExpenses->is_sys = 0;
		$modelExpenses->trips_id = $modelTrips->id;
		$modelExpenses->users_id = $user_id;
		$modelExpenses->spending_date = $modelTrips->departure_date;
		$modelExpenses->countries_id = $modelTrips->countries_id;
		$modelExpenses->payments_methods_id = 2;
		$modelExpenses->expenses_types_id = 6;
		if (Yii::$app->request->isPost && $modelExpenses->load(Yii::$app->request->post())) {
			$modelExpenses->filesList = UploadedFile::getInstances($modelExpenses, 'filesList');
			if ($modelExpenses->validate()) {
				if ($modelExpenses->save()) {
					$modelExpenses->uploadFilesList();
					$modelTrips->generateEshel();
					return $this->redirect(['expenses-new', 'trip_id' => $modelTrips->id]);
				}
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$currency_codes = CurrencyCodes::listAll();
		$expenses_types = ExpensesTypes::listAll();
		$payments_methods = PaymentsMethods::listAll();

		return $this->render('_form_expenses', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'currency_codes' => $currency_codes,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'modelExpenses' => $modelExpenses,
		]);
	}

	public function actionExpensesUpdate($trip_id, $expense_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelExpenses = Expenses::find()
			->where(['id' => $expense_id])
			->andWhere(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->limit(1)
			->one();
		if (!$modelExpenses || $modelExpenses->is_sys) {
			if ($modelExpenses->expenses_types_id == 2) {
				return $this->render('_form_expenses_');
			} else {
				throw new NotFoundHttpException('Page not found');
			}
		}
		$modelExpenses->filesList = UploadedFile::getInstances($modelExpenses, 'filesList');
		$modelExpenses->scenario = Expenses::SCENARIO_NEW;
		if ($modelExpenses->load(Yii::$app->request->post())) {
			if ($modelExpenses->validate()) {
				if ($modelExpenses->save()) {
					$modelTrips->generateEshel();
					$modelExpenses->uploadFilesList();
					return $this->redirect(['view', 'id' => $modelTrips->id]);
				}
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$currency_codes = CurrencyCodes::listAll();
		$expenses_types = ExpensesTypes::listAll();
		$payments_methods = PaymentsMethods::listAll();
		$files = $modelExpenses->files;

		return $this->render('_form_expenses', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'currency_codes' => $currency_codes,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'modelExpenses' => $modelExpenses,
			'files' => $files,
		]);
	}

	public function actionExpensesUpdateEshel($trip_id, $expense_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelExpenses = Expenses::find()
			->where(['id' => $expense_id])
			->andWhere(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->limit(1)
			->one();

		$modelExpenses->scenario = Expenses::SCENARIO_ESHEL;
		if ($modelExpenses->load(Yii::$app->request->post())) {
			if ($modelExpenses->validate()) {
				$modelExpenses->updateAmount();
				if ($modelExpenses->save()) {
					return $this->redirect(['view', 'id' => $modelTrips->id]);
				}
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$currency_codes = CurrencyCodes::listAll();
		$expenses_types = ExpensesTypes::listAll();
		$payments_methods = PaymentsMethods::listAll();

		return $this->render('_form_expenses_eshel', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'currency_codes' => $currency_codes,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'modelExpenses' => $modelExpenses,
		]);
	}

	public function actionExpensesUpdateFiles($trip_id, $expense_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelExpenses = Expenses::find()
			->where(['id' => $expense_id])
			->andWhere(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->limit(1)
			->one();

		$modelExpenses->scenario = Expenses::SCENARIO_UPLOAD;
		if (Yii::$app->request->isPost && $modelExpenses->load(Yii::$app->request->post())) {
			$modelExpenses->filesList = UploadedFile::getInstances($modelExpenses, 'filesList');
			if ($modelExpenses->validate()) {
				$modelExpenses->uploadFilesList();
				return $this->redirect(['trips/expenses-update-files', 'trip_id' => $modelTrips->id, 'expense_id' => $modelExpenses->id]);
			}
		}
		$user_plant = $modelTrips->plant;
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$currency_codes = CurrencyCodes::listAll();
		$expenses_types = ExpensesTypes::listAll();
		$payments_methods = PaymentsMethods::listAll();

		return $this->render('_form_expenses_files', [
			'user_plant' => $user_plant,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'currency_codes' => $currency_codes,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'modelExpenses' => $modelExpenses,
		]);
	}

	public function actionExpensesVacations($trip_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelsExpenses = Expenses::find()
			->where(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->andWhere(['is_sys' => 1])
			->andWhere(['in', 'expenses_types_id', [1, 2]])
			->all();
		if (!$modelsExpenses) {
			throw new UserException('You do not have full business days on a business trip to mark them as a vacation at your own expense.');
		}
		foreach ($modelsExpenses as $modelExpenses) {
			$modelExpenses->scenario = Expenses::SCENARIO_VACATION;
			if ($modelExpenses->expenses_types_id == 1) {
				$modelExpenses->vacation = 1;
			}
		}
		DynamicModel::loadMultiple($modelsExpenses, Yii::$app->request->post());
		if (Yii::$app->request->isPost && DynamicModel::validateMultiple($modelsExpenses)) {
			$transaction = \Yii::$app->db->beginTransaction();
			try {
				foreach ($modelsExpenses as $modelExpenses) {
					if ($modelExpenses->vacation == 1 && $modelExpenses->expenses_types_id == 2) {
						$modelExpenses->expenses_types_id = 1;
					} elseif ($modelExpenses->vacation == 0 && $modelExpenses->expenses_types_id == 1) {
						$modelExpenses->expenses_types_id = 2;
					}
					$modelExpenses->save(false);
				}
				$transaction->commit();
			} catch (Exception $e) {
				$transaction->rollBack();
			}
			return $this->redirect(['view', 'id' => $modelTrips->id]);
		}
		$user_plant = $modelTrips->plant;
		return $this->render('_form_vacations', [
			'user_plant' => $user_plant,
			'modelTrips' => $modelTrips,
			'modelsExpenses' => $modelsExpenses,
		]);
	}

	public function actionExpensesEshel($trip_id)
	{
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->oneByIdAndUserId($trip_id, $user_id);
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		$modelsExpenses = Expenses::find()
			->where(['trips_id' => $modelTrips->id])
			->andWhere(['users_id' => $user_id])
			->andWhere(['is_sys' => 1])
			->andWhere(['in', 'expenses_types_id', [2]])
			->all();
		if (!$modelsExpenses) {
			throw new UserException('Error');
		}
		foreach ($modelsExpenses as $modelExpenses) {
			$modelExpenses->scenario = Expenses::SCENARIO_NIGHT;

		}
		DynamicModel::loadMultiple($modelsExpenses, Yii::$app->request->post());
		if (Yii::$app->request->isPost && DynamicModel::validateMultiple($modelsExpenses)) {
			$transaction = \Yii::$app->db->beginTransaction();
			try {
				foreach ($modelsExpenses as $modelExpenses) {
					$modelExpenses->updateAmount();
					$modelExpenses->save(false);
				}
				$transaction->commit();
			} catch (Exception $e) {
				$transaction->rollBack();
			}
			return $this->redirect(['view', 'id' => $modelTrips->id]);
		}
		$user_plant = $modelTrips->plant;
		return $this->render('_form_eshel_night.php', [
			'user_plant' => $user_plant,
			'modelTrips' => $modelTrips,
			'modelsExpenses' => $modelsExpenses,
		]);
	}

	public function actionPdf($id) {
		$user_id = Yii::$app->user->id;
		$modelTrips = Trips::find()->where(['id' => $id])->limit(1)->one();
		if (!$modelTrips) {
			throw new NotFoundHttpException('Page not found');
		}
		if ($user_id != $modelTrips->users_id) {
			throw new \yii\web\ForbiddenHttpException('Only the trip creator can view this page');
		}
		if ($modelTrips->status != '10' && $modelTrips->status != '11') {
			throw new \yii\web\ForbiddenHttpException('This page is only available for the end of the trip');
		}
		$user = Users::findOne($modelTrips->users_id);
		$user_plant = $user->plant;
		$modelsFlights = Flights::find()->where(['trips_id' => $modelTrips->id])->orderBy('departure_date ASC, departure_time ASC')->all();
		$modelsExpenses = Expenses::find()->where(['trips_id' => $modelTrips->id])->orderBy('spending_date ASC, is_sys DESC')->all();
		$modelsFirstAmounts = FirstAmounts::find()->where(['trips_id' => $modelTrips->id])->all();
		$currency_codes = CurrencyCodes::listAll();
		$trips_types = TripsTypes::listAll();
		$countries = Countries::listAll();
		$expenses_types = ExpensesTypes::listAllWithSys();
		$payments_methods = PaymentsMethods::listAll();

		$html = $this->renderPartial('@app/views/trips-export/pdf', [
			'user_plant' => $user_plant,
			'currency_codes' => $currency_codes,
			'trips_types' => $trips_types,
			'countries' => $countries,
			'expenses_types' => $expenses_types,
			'payments_methods' => $payments_methods,
			'modelTrips' => $modelTrips,
			'firstAmountsDataProvider' => $modelsFirstAmounts,
			'modelsFirstAmountsAll' => $modelsFirstAmounts,
			'flightsDataProvider' => $modelsFlights,
			'expensesDataProvider' => $modelsExpenses,
		]);

		Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
		$pdf = new Pdf([
			'mode' => Pdf::MODE_UTF8, // leaner size using standard fonts
			'format' => Pdf::FORMAT_A4,
			'orientation' => Pdf::ORIENT_LANDSCAPE,
			'destination' => Pdf::DEST_BROWSER,
			'content' => $html,
			'options' => [
				'title' => 'EV Travel: Trip #'.$modelTrips->id,
			],
			'methods' => [
				'SetHeader' => ['EGMO & Vargus: EV Travel Trip #'.$modelTrips->id.'||Generated On: '.Yii::$app->formatter->format('now', 'datetime')],
				'SetFooter' => ['|Page {PAGENO}|'],
			]
		]);
		return $pdf->render();
	}

}
