<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\builder\Form;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;
use yii\widgets\MaskedInput;

/* @var $this yii\web\View */

$this->title = ($modelExpenses->isNewRecord) ? 'Create expense' : 'Edit eshel';

$this->params['breadcrumbs'][] = ['url' => ['trips/index'], 'label' => 'Trips'];
$this->params['breadcrumbs'][] = ['url' => ['trips/view', 'id' => $modelTrips->id], 'label' => 'Trip #' . $modelTrips->id];
$this->params['breadcrumbs'][] = ['label' => 'Eshel'];
$this->params['breadcrumbs'][] = ($modelExpenses->isNewRecord) ? 'Create' : 'Edit #'.$modelTrips->id;

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
		<?= Html::submitButton(Yii::t('app', ($modelExpenses->isNewRecord) ? 'Create' : 'Save' ), ['class' => 'btn btn-secondary btn-block btn-breadcrumb']) ?>
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
						<td colspan="3"><b>Company:</b> <?= Html::encode(Yii::$app->params['plant_ids'][$modelTrips->plant]) ?></td>
					</tr>
					<?php
					}
					?>
					<tr>
						<td><b>Trip Purpose:</b> <?= Html::encode($modelTrips->tripsType->name_en) ?></td>
						<td><b>State/Country:</b> <?= Html::encode($modelTrips->country->country_en) ?></td>
						<td><b>City:</b> <?= Html::encode($modelTrips->city) ?></td>
					</tr>
					<tr>
						<td><b>Departure/Arrival Dates:</b> <?= Yii::$app->formatter->format($modelTrips->departure_date, 'date') . ' - ' . Yii::$app->formatter->format($modelTrips->arrival_date, 'date') ?></td>
						<td colspan="2"><b>Trip Status:</b> <?= Html::encode($modelTrips->getStatusShort()) ?></td>
					</tr>
					<tr>
						<td colspan="3"><b>Notes:</b> <?= Html::encode($modelTrips->user_note) ?></td>
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

echo Form::widget([ // 6 column layout
	'model'=>$modelExpenses,
	'form'=>$form,
	'columns'=>2,
	'compactGrid'=>true,
	'attributes'=>[

		'trips_types_id'=>[
			'type'=>Form::INPUT_WIDGET, 
			'widgetClass'=>'\kartik\select2\Select2', 
			'options'=>[
				'data' => $trips_types,
				'options' => ['placeholder' => ''],
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
				'options' => ['placeholder' => ''],
				'pluginOptions' => [
					'allowClear' => false,
				],
			], 
		],
	]
]);

?>

	</div>
</div>

<?php ActiveForm::end(); ?>