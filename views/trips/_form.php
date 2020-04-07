<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use kartik\form\ActiveForm;
use kartik\builder\Form;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;
use yii\widgets\MaskedInput;
use wbraganca\dynamicform\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Trips */
/* @var $form ActiveForm */
/* @var $currency_codes */

$this->title = ($modelTrips->isNewRecord) ? 'Create trip' : 'Edit trip #' . $modelTrips->id;

$this->params['breadcrumbs'][] = ['url' => ['trips/index'], 'label' => 'Trips'];
if (!$modelTrips->isNewRecord) {
	$this->params['breadcrumbs'][] = ['url' => ['trips/view', 'id' => $modelTrips->id], 'label' => 'Trip #' . $modelTrips->id];
}
$this->params['breadcrumbs'][] = ($modelTrips->isNewRecord) ? 'Create' : 'Edit';

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
		<?= Html::submitButton(Yii::t('app', ($modelTrips->isNewRecord) ? 'Create' : 'Save' ), ['class' => 'btn btn-secondary btn-block btn-breadcrumb']) ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<button type="button" class="btn btn-secondary btn-block btn-breadcrumb" onclick="window.history.back()">Back</button>
	</div>
</div>

<div class="row">
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
		if (in_array($user_plant, [100, 110])) {
			// plant
			echo Form::widget([ // 1 column layout
				'model'=>$modelTrips,
				'form'=>$form,
				'columns'=>1,
				'compactGrid'=>true,
				'attributes'=>[
					'plant'=>[
						'type'=>Form::INPUT_WIDGET, 
						'widgetClass'=>'\kartik\select2\Select2', 
						'options'=>[
							'data' => Yii::$app->params['plant_ids'],
							'options' => ['placeholder' => 'Plant'],
							'pluginOptions' => [
								'allowClear' => false,
							],
						], 
					],
				]
			]);
		}

		// type selec2, country select2, city text
		echo Form::widget([ // 3 column layout
			'model'=>$modelTrips,
			'form'=>$form,
			'columns'=>3,
			'compactGrid'=>true,
			'attributes'=>[
				'trips_types_id'=>[
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
				'countries_id'=>[
					'type'=>Form::INPUT_WIDGET, 
					'widgetClass'=>'\kartik\select2\Select2', 
					'options'=>[
						'data' => $countries,
						'options' => ['placeholder' => 'Country'],
						'pluginOptions' => [
							'allowClear' => false,
						],
					], 
				],
				'city'=>[
					'type'=>Form::INPUT_TEXT, 
				],
			]
		]);
		// user_note text, departure_date/arrival_date DateRangePicker
		echo Form::widget([ // 3 column layout
			'model'=>$modelTrips,
			'form'=>$form,
			'columns'=>2,
			'compactGrid'=>true,
			'attributes'=>[
				'date_range' => [
					'label' => 'Departure/Arrival date',
					'attributes'=>[
						'departure_date' => [
							'type'=>Form::INPUT_WIDGET, 
							'widgetClass'=>'\kartik\datecontrol\DateControl',
							'options'=>[
								'readonly' => true,
								'widgetOptions' => [
									'pluginOptions' => [
										'autoclose' => true,
										'todayHighlight' => false,
									],
									'pluginEvents' => [
										'changeDate' => new JsExpression('function(e) {
											console.log(e);
											jQuery("#trips-arrival_date-disp-kvdate").kvDatepicker("setDate", e.date);
										}'),
									],
								],
								'options'=>[
									'options'=>['placeholder'=>'Date from...'],
								]
							],
						],
						'arrival_date'=>[
							'type'=>Form::INPUT_WIDGET, 
							'widgetClass'=>'\kartik\datecontrol\DateControl', 
							'options'=>[
								'readonly' => true,
								'widgetOptions' => [
									'pluginOptions' => [
										'autoclose' => true,
										'todayHighlight' => false,
									],
								],
								'options'=>[
									'options'=>['placeholder'=>'Date to...', 'class'=>'col-md-9'],
								]
							],
						],
					],
				],
				'note' => [
					'label' => 'Note',
					'attributes'=>[
						'user_note'=>[
							'type'=>Form::INPUT_TEXT, 
						],
					],
				],
			]
		]);

		?>


			<?php  DynamicFormWidget::begin([
				'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
				'widgetBody' => '.container-items', // required: css class selector
				'widgetItem' => '.item', // required: css class
				'limit' => 9, // the maximum times, an element can be cloned (default 999)
				'min' => 1, // 0 or 1 (default 1)
				'insertButton' => '.add-item', // css class
				'deleteButton' => '.remove-item', // css class
				'model' => $modelsFirstAmounts[0],
				'formId' => 'dynamic-form',
				'formFields' => [
					'first_amount',
					'currency_codes_id',
				],
			]); ?>
			<div class="row pt-3">
				<div class="col-md-auto">
					<h1 class="text-center">Advance Payments</h1>
				</div>
				<div class="col">
					<div class="col-md-4 col-lg-2 col-padding-zero btn-header-valign">
						<button type="button" class="btn btn-light btn-sm add-item"><i class="fas fa-plus"></i></button>
					</div>
				</div>
				<div class="w-100"></div>
				<div class="col">
					<hr class="m-0 pb-1">
				</div>
				<div class="w-100"></div>
				<div class="col">

		<div class="row container-items">

			<?php foreach ($modelsFirstAmounts as $index => $modelFirstAmounts) { ?>
				<div class="col-sm-4 item">
					<div class="alert alert-secondary">
								<button type="button" class="close remove-item">
									<span aria-hidden="true">&times;</span>
								</button>
						<div class="row">
							<div class="col-xl-6">
							<?php
							// necessary for update action.
							if (!$modelFirstAmounts->isNewRecord) {
								echo Html::activeHiddenInput($modelFirstAmounts, "[{$index}]id");
							}
							?>
							<?= $form->field($modelFirstAmounts, "[{$index}]first_amount")->widget(yii\widgets\MaskedInput::class, [
								'clientOptions' => [
									'alias' =>  'numeric',
									'groupSeparator' => ',',
									'autoGroup' => false,
									'digits' => 2,
									'digitsOptional' => false,
									'placeholder' => '0',
								],
								'options' => [
									'placeholder' => 'Sum',
									'class' => 'form-control',
								],
							])->label(false) ?>


							</div>
							<div class="col-xl-6">
							<?= $form->field($modelFirstAmounts, "[{$index}]currency_codes_id")->widget(Select2::classname(), [
								'data' => $currency_codes,
								'options' => ['placeholder' => 'Currency'],
								'pluginOptions' => [
									'allowClear' => false
								],
							])->label(false) ?>
							</div>

						</div>

				</div>
			</div>
		  


			<?php } ?>
		</div>
		<?php DynamicFormWidget::end();  ?>

		</div>
	</div>


	</div>
</div>
<?php ActiveForm::end(); ?>