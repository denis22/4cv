<?php

use yii\helpers\Html;
use yii\helpers\Url;
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
	<div class="col">

		<div class="row">
			<div class="col-3">
				<?= $form->field($modelExpenses, 'filesList[]')->fileInput(['multiple' => true, 'accept' => '*/*'])->label(false) ?>
			</div>
			<div class="col-2">
				<?= Html::submitButton(Yii::t('app', 'Upload Files' ), ['class' => 'btn btn-sm btn-secondary']) ?>
			</div>
		</div>

	</div>
	<div class="w-100"></div>
	<div class="col">
		<hr class="m-0">
	</div>
	<div class="w-100"></div>
	<div class="col">


		<?php
		$files = $modelExpenses->files;
		if ($files) {
			$path = '/files/' . $modelTrips->users_id . '/e/' . $modelExpenses->id . '/';
		?>
			<table class="table table-sm table-striped">
				<thead>
					<tr>
						<th scope="col">Files</th>
						<th scope="col"><div class="float-right">Delete</div></th>
					</tr>
				</thead>
				<tbody>
		<?php
			foreach ($files as $file) {
		?>
					<tr>
						<th scope="row"><?=Html::a(Html::encode($file->path), $file->url(), ['target' => '_blank'])?></th>
						<td>
							<div class="float-right">
								<a href="<?=Url::to(['trips/file-delete2', 'id'=>$file->id, 'trip' => $modelTrips->id, 'expense' => $file->expenses_id])?>"><i class="fas fa-trash-alt"></i></a>
							</div>
						</td>
					</tr>
		<?php
			}
		?>
				</tbody>
			</table>
		<?php
		}
		?>

	</div>
</div>
<?php ActiveForm::end(); ?>