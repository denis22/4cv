<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\builder\Form;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;
use yii\widgets\MaskedInput;

/* @var $this yii\web\View */

$this->title = 'Set nights';

$this->params['breadcrumbs'][] = ['url' => ['trips/index'], 'label' => 'Trips'];
$this->params['breadcrumbs'][] = ['url' => ['trips/view', 'id' => $modelTrips->id], 'label' => 'Trip #' . $modelTrips->id];
$this->params['breadcrumbs'][] = ['label' => 'Eshel'];
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>
<?php
$form = ActiveForm::begin([
	'id' => 'main-form',
]); 
?>
<div class="row">
	<div class="col">
		<?= $this->render('@app/views/layouts/breadcrumbs') ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-secondary btn-block btn-breadcrumb']) ?>
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
							<?php
							if (!empty($modelTrips->user_note)) {
							?>
							<tr>
								<td colspan="3"><b>Note:</b> <?= Html::encode($modelTrips->user_note) ?></td>
							</tr>
							<?php
							}
							if (!empty($modelTrips->checker_note)) {
							?>
							<tr>
								<td colspan="3"><b>Checker note:</b> <?= Html::encode($modelTrips->checker_note) ?></td>
							</tr>
							<?php
							}
							?>
				</tbody>
			</table>
		</div>

	</div>
</div>

<div class="row pt-1">
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
	if ($modelsExpenses && count($modelsExpenses)>0) {
		foreach ($modelsExpenses as $index => $modelExpenses) {
	?>

	<div class="form-check">
		<?= Html::activeCheckbox($modelExpenses, "[{$index}]is_night", [
				'label' => false,
				'class' => 'form-check-input',
				'id' => 'ExpensesNight' . $modelExpenses->id,
			]) ?>
		<label class="form-check-label" for="ExpensesNight<?= $modelExpenses->id ?>">
			<?= Yii::$app->formatter->format($modelExpenses->spending_date, 'date') ?>
		</label>
	</div>

	<?php
		}
	}
	?>
	

	</div>
</div>

<?php ActiveForm::end(); ?>