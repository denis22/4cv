<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\builder\Form;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;
use yii\widgets\MaskedInput;

/* @var $this yii\web\View */

$this->title = ($modelFlights->isNewRecord) ? 'Create flight' : 'Edit flight';

$this->params['breadcrumbs'][] = ['url' => ['trips/index'], 'label' => 'Trips'];
$this->params['breadcrumbs'][] = ['url' => ['trips/view', 'id' => $modelTrips->id], 'label' => 'Trip #' . $modelTrips->id];
$this->params['breadcrumbs'][] = ['label' => 'Flights'];
$this->params['breadcrumbs'][] = ($modelFlights->isNewRecord) ? 'Create' : 'Edit #'.$modelTrips->id;

?>
<?php
$form = ActiveForm::begin([
	'id' => 'dynamic-form', 
	'type' => ActiveForm::TYPE_VERTICAL
]); 
?>

<div class="row">
	<div class="col">
		<?= $this->render('@app/views/layouts/breadcrumbs') ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= Html::submitButton(Yii::t('app', ($modelFlights->isNewRecord) ? 'Create' : 'Save' ), ['class' => 'btn btn-secondary btn-block btn-breadcrumb']) ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<button type="button" class="btn btn-secondary btn-block btn-breadcrumb" onclick="window.history.back()">Back</button>
	</div>
</div>

<div class="row trip-box my-3">
	<div class="col-md-auto">
		<h1 class="text-center">Trip #<?= $modelTrips->id ?></h1>
	</div>
	<div class="w-100"></div>
	<div class="col">

		<div class="table-responsive-xl">
			<table class="table table-sm table-hover">
				<tbody>
					<?php
					if (in_array($user_plant, [100, 110])) {
					?>
					<tr>
						<td colspan="3"><b>Plant:</b> <?= Html::encode(Yii::$app->params['plant_ids'][$modelTrips->plant]) ?></td>
					</tr>
					<?php
					}
					?>
					<tr>
						<td><b>Trip type:</b> <?= Html::encode($modelTrips->tripsType->name_en) ?></td>
						<td><b>Country:</b> <?= Html::encode($modelTrips->country->country_en) ?></td>
						<td><b>City:</b> <?= Html::encode($modelTrips->city) ?></td>
					</tr>
					<tr>
						<td><b>Departure/Arrival date:</b> <?= Yii::$app->formatter->format($modelTrips->departure_date, 'date') . ' - ' . Yii::$app->formatter->format($modelTrips->arrival_date, 'date') ?></td>
						<td colspan="2"><b>Trip status:</b> <?= Html::encode($modelTrips->getStatusShort()) ?></td>
					</tr>
					<tr>
						<td colspan="3"><b>Note:</b> <?= Html::encode($modelTrips->user_note) ?></td>
					</tr>
				</tbody>
			</table>
		</div>

	</div>
</div>

<div class="row pt-3">
	<div class="col-md-auto">
		<h1 class="text-center"><?= $this->title ?></h1>
	</div>
	<div class="w-100"></div>
	<div class="col">
		<hr class="m-0">
	</div>
	<div class="w-100"></div>
	<div class="col">


<?php

echo Form::widget([
	'model'=>$modelFlights,
	'form'=>$form,
	'columns'=>2,
	'compactGrid'=>true,
	'attributes'=>[
		'trips_types_is_night' => [
			'attributes'=>[
				'trips_types_id'=>[
					'label' => 'Trip type',
					'type'=>Form::INPUT_WIDGET, 
					'widgetClass'=>'\kartik\select2\Select2', 
					'options'=>[
						'data' => $trips_types,
						'options' => ['placeholder' => 'Trip type'],
						'pluginOptions' => [
							'allowClear' => false,
						],
					],
				],
				'is_night_trip'=>[
					'label' => 'Night Trip',
					'type'=>Form::INPUT_DROPDOWN_LIST,
					'items' => ['No', 'Yes'],
					'options'=>[
						'data-toggle'=>'tooltip',
						'data-placement'=>'top',
						'title'=>'Flight during (On board) from 12am to 5am',
						//'class'=>'col-md-9',
					],
				],
			],
		],
		'from_country_city' => [
			'attributes'=>[
				'from_country'=>[
					'label' => 'From country',
					'type'=>Form::INPUT_WIDGET, 
					'widgetClass'=>'\kartik\select2\Select2', 
					'options'=>[
						'data' => $countries,
						'options' => ['placeholder' => 'From country'],
						'pluginOptions' => [
							'allowClear' => false,
						],
					], 
				],
				'from_city'=>[
					'label' => 'From city',
					'type'=>Form::INPUT_TEXT,
					'options'=>[
						'placeholder'=>'From city',
						'class'=>'col-md-9',
					],
				],
			],
		],
	]
]);

echo Form::widget([
	'model'=>$modelFlights,
	'form'=>$form,
	'columns'=>2,
	'compactGrid'=>true,
	'attributes'=>[
		'departure_date_time' => [
			'attributes'=>[
				'departure_date' => [
					'label' => 'Departure date',
					'type'=>Form::INPUT_WIDGET, 
					'widgetClass'=>'\kartik\datecontrol\DateControl',
					'options'=>[
						'readonly' => true,
						'widgetOptions' => [
							'pluginOptions' => [
								'autoclose' => true,
								'todayHighlight' => false,
								'startDate' => Yii::$app->formatter->asDate($modelTrips->departure_date, 'php:d-m-Y'),
								'endDate' => Yii::$app->formatter->asDate($modelTrips->arrival_date, 'php:d-m-Y'),
							],
						],
						'options'=>[
							'options'=>['placeholder'=>'Departure date'],
						]
					],
				],
				'departure_time'=>[
					'label' => 'Departure time',
					'type'=>Form::INPUT_WIDGET,
					'widgetClass'=>'\kartik\datecontrol\DateControl',
					'value' => '00:00:00',
					'options'=>[
						'type' => DateControl::FORMAT_TIME,
						'readonly' => true,
						'widgetOptions' => [
							'pluginOptions' => [
							],
						],
						'options'=>[
							'options'=>[
							],
						]
					],
				],
			],
		],
		'to_country_city' => [
			'attributes'=>[
				'to_country'=>[
					'label' => 'To country',
					'type'=>Form::INPUT_WIDGET, 
					'widgetClass'=>'\kartik\select2\Select2', 
					'options'=>[
						'data' => $countries,
						'options' => ['placeholder' => 'To country'],
						'pluginOptions' => [
							'allowClear' => false,
						],
					], 
				],
				'to_city'=>[
					'label' => 'To city',
					'type'=>Form::INPUT_TEXT,
					'options'=>[
						'placeholder'=>'To city',
						'class'=>'col-md-9',
					],
				],
			],
		],
	]
]);

echo Form::widget([
	'model'=>$modelFlights,
	'form'=>$form,
	'columns'=>1,
	'compactGrid'=>true,
	'attributes'=>[
		'user_description'=>[
			'type'=>Form::INPUT_TEXT, 
		],
	]
]);
?>

	</div>
</div>

<?php ActiveForm::end(); ?>