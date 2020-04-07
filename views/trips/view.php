<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\helpers\HtmlHelper;
use kartik\grid\GridView;
use kartik\form\ActiveForm;
use kartik\builder\Form;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;

$this->title = 'Trip #'.$modelTrips->id;

$this->params['breadcrumbs'][] = ['url' => ['trips/index'], 'label' => 'Trips'];
$this->params['breadcrumbs'][] = ['label' => 'Trip #' . $modelTrips->id];
$this->params['breadcrumbs'][] = 'View';

$firstAmountsList = [];
$firstAmounts = $modelTrips->firstAmounts;
if (count($firstAmounts) > 0) {
	foreach ($modelTrips->firstAmounts as $firstAmount) {
		$firstAmountsList[] = $firstAmount->currencyCode->cc . ' ' . number_format($firstAmount->first_amount, 2, '.', ',') . ' ' . $firstAmount->currencyCode->currency_sign;
	}
}

$expenses = $modelTrips->expenses;
$set_vacations = false;
foreach ($expenses as $expense) {
	if ($expense->is_sys == 1 && ($expense->expenses_types_id == 1 || $expense->expenses_types_id == 2)) {
		$set_vacations = true;
	}
}

?>
<div class="row">
	<div class="col">
		<?= $this->render('@app/views/layouts/breadcrumbs') ?>
	</div>
<?php
if ($modelTrips->status == '10' || $modelTrips->status == '11') {
?>
	<div class="col-md-auto d-lg-block d-print-none">
		<a class="btn btn-warning btn-block btn-breadcrumb" href="<?=Url::to(['trips/pdf', 'id'=>$modelTrips->id])?>" target="_blank">PDF Preview</a>
	</div>
<?php
}
?>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= HtmlHelper::btn(['trips/flights-new', 'trip_id' => $modelTrips->id], 'Add Flight', 'btn btn-secondary btn-block btn-breadcrumb', $modelTrips->canAddFlights()) ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= HtmlHelper::btn(['trips/expenses-new', 'trip_id' => $modelTrips->id], 'Add Expenses', 'btn btn-secondary btn-block btn-breadcrumb', $modelTrips->canAddExpenses()) ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= HtmlHelper::btn(['trips/send', 'id' => $modelTrips->id], 'Send for approval', 'btn btn-secondary btn-block btn-breadcrumb', ($modelTrips->canSendFlights() || $modelTrips->canSendExpenses())) ?>
	</div>

	<div class="col-md-auto d-lg-block d-print-none">
		<button type="button" class="btn btn-secondary btn-block btn-breadcrumb" onclick="window.history.back()">Back</button>
	</div>
</div>

<div class="row trip-box my-3">
	<div class="col-md-auto">
		<h1 class="text-center"><?= $this->title ?></h1>
	</div>
	<div class="col-md-auto">
		<div class="col-md-auto btn-header-valign">
			<?= HtmlHelper::btn(['trips/update', 'id' => $modelTrips->id], '<i class="fas fa-pencil-alt"></i>', 'btn btn-light btn-block btn-sm', $modelTrips->canUpdate(), 'Edit trip #'.$modelTrips->id) ?>
		</div>
	</div>
	<div class="w-100"></div>
	<div class="col">

		<div class="row">
			<div class="col-12">

				<div class="table-responsive-md">
					<table class="table table-sm table-hover">
						<tbody>
							<?php
							if (in_array($user_plant, [100, 110])) {
							?>
							<tr>
								<td colspan="4"><b>Company:</b> <?= Html::encode(Yii::$app->params['plant_ids'][$modelTrips->plant]) ?></td>
							</tr>
							<?php
							}
							?>
							<tr>
								<td><b>Trip Purpose:</b> <?= Html::encode($modelTrips->tripsType->name_en) ?></td>
								<td><b>Country:</b> <?= Html::encode($modelTrips->country->country_en) ?></td>
								<td><b>City:</b> <?= Html::encode($modelTrips->city) ?></td>
								<td><b>Trip Status:</b> <?= Html::encode($modelTrips->getStatusShort()) ?></td>
							</tr>
							<tr>
								<td><b>Departure/Arrival Dates:</b> <?= Yii::$app->formatter->format($modelTrips->departure_date, 'date') . ' - ' . Yii::$app->formatter->format($modelTrips->arrival_date, 'date') ?></td>
								<td colspan="3">
									<?php
									if (count($firstAmountsList) > 0) {
									?>
									<b>Advance Payments:</b> <?= implode(', ', $firstAmountsList) ?>
									<?php
									} else {
									?>
									No cash requested. Edit the trip to add.
									<?php
									}
									?>
								</td>
							</tr>
							<?php
							if (!empty($modelTrips->user_note)) {
							?>
							<tr>
								<td colspan="4"><b>Notes:</b> <?= Html::encode($modelTrips->user_note) ?></td>
							</tr>
							<?php
							}
							if (!empty($modelTrips->checker_note)) {
							?>
							<tr>
								<td colspan="4"><b>Notes:</b> <?= Html::encode($modelTrips->checker_note) ?></td>
							</tr>
							<?php
							}
							?>
						</tbody>
					</table>
				</div>

		<?php
		$form = ActiveForm::begin([
			'id' => 'dynamic-form', 
				'type' => ActiveForm::TYPE_VERTICAL
		]); 
		?>

<div class="row">
	<div class="col-3">
		<?= $form->field($modelTrips, 'filesList[]')->fileInput(['multiple' => true, 'accept' => '*/*'])->label(false) ?>
	</div>
	<div class="col-2">
		<?= Html::submitButton(Yii::t('app', 'Upload Files' ), ['class' => 'btn btn-sm btn-secondary']) ?>
	</div>
</div>


		<?php ActiveForm::end(); ?>		
		

		<?php
		$files = $modelTrips->files;
		if ($modelTrips->files) {
			$path = '/files/' . $modelTrips->users_id . '/' . $modelTrips->id . '/';
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
								<a href="<?=Url::to(['trips/file-delete', 'id'=>$file->id])?>"><i class="fas fa-trash-alt"></i></a>
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


	</div>
</div>

<div class="row pt-1">
	<div class="col-md-auto">
		<h1 class="text-center">Flights</h1>
	</div>

	<div class="w-100"></div>
	<div class="col">
		<hr class="m-0">
	</div>
	<div class="w-100"></div>
	<div class="col">
	<?= GridView::widget([
		'dataProvider' => $flightsDataProvider,
		'tableOptions' => [
			'class' => 'table-sm',
		],
		'columns' => [
			[
				'class' => 'kartik\grid\SerialColumn',
				'width' => '36px',
				'header' => '№',
			],
			[
				'attribute' => 'trips_types_id',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->tripsType->name_en;
				},
			],
			[
				'attribute' => 'from_country',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->fromCountry->country_en . '/' . $model->from_city . 
						' - ' . $model->toCountry->country_en . '/' . $model->to_city;
				},
			],
			[
				'attribute' => 'departure_date',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return Yii::$app->formatter->format($model->departure_date, 'date') . 
						' ' . Yii::$app->formatter->format($model->departure_time, 'time');
				},
			],
			[
				'attribute' => 'is_night_trip',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return ($model->is_night_trip) ? 'Yes' : 'No';
				},
			],
			'user_description',
			[
				'class' => '\kartik\grid\ActionColumn',
				'template' => '{update} {delete}',
				
				'buttons' => [
					'update' => function ($url, $model) use ($modelTrips) {
						return Html::a('<span class="fas fa-pencil-alt"></span>', [
							'trips/flights-update',
							'trip_id' => $modelTrips->id,
							'flight_id' => $model->id
						]);
					},
					'delete' => function ($url, $model) use ($modelTrips) {
						return Html::a('<span class="fas fa-trash-alt"></span>', [
							'trips/flights-delete',
							'trip_id' => $modelTrips->id,
							'flight_id' => $model->id
						]);
					},
				],

				'visibleButtons' => [
					'update' => function ($model, $key, $index) use ($modelTrips) {
						return $modelTrips->canUpdateFlights();
					},
					
					'delete' => function ($model, $key, $index) use ($modelTrips) {
						return $modelTrips->canDeleteFlights();
					},
					
				],

			],
		],

	]); ?>
	</div>
</div>

<div class="row pt-1">
	<div class="col-md-auto">
		<h1 class="text-center">Expenses</h1>
	</div>

	<div class="col-md-auto">
		<div class="col-md-auto">
			<?= HtmlHelper::btn(['trips/expenses-vacations', 'trip_id' => $modelTrips->id], '<img src="/images/sun-umbrella.png">', 'btn btn-light btn-block btn-sm', $modelTrips->canAddExpenses() && $set_vacations, 'Set vacations') ?>
		</div>
	</div>
	<div class="col-md-auto">
		<div class="col-md-auto">
			<?= HtmlHelper::btn(['trips/expenses-eshel', 'trip_id' => $modelTrips->id], '<i class="fas fa-moon"></i>', 'btn btn-light btn-block btn-lg', $modelTrips->canAddExpenses() && $set_vacations, 'Set nights (Flight during (On board) from 12am to 5am)') ?>
		</div>
	</div>

	<div class="w-100"></div>
	<div class="col">
		<hr class="m-0">
	</div>
	<div class="w-100"></div>
	<div class="col">
	<?= GridView::widget([
		'dataProvider' => $expensesDataProvider,
		'tableOptions' => [
			'class' => 'table-sm',
		],
		'columns' => [
			[
				'class' => 'kartik\grid\SerialColumn',
				'width' => '36px',
				'header' => '№',
			],
			[
				'attribute' => 'spending_date',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return Yii::$app->formatter->format($model->spending_date, 'date');
				},
			],
			[
				'attribute' => 'countries_id',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->country->country_en;
				},
			],
			[
				'attribute' => 'expenses_types_id',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->expensesType->name_en;
				},
			],
			[
				'attribute' => 'trips_types_id',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) {
					$tripType = $model->tripsType;
					if ($tripType) {
					 	return $tripType->name_en;
					}
					return '';
				},
			],
			[
				'attribute' => 'is_night',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) {
					if ($model->is_night) {
					 	return 'Yes';
					}
					return '';
				},
			],
			[
				'attribute' => 'payments_methods_id',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->paymentsMethod->name_en;
				},
			],
			[
				'attribute' => 'amount',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) {
					if ($model->expenses_types_id == 1) {
						return '';
					}
					return $model->currencyCode->cc . ' ' . $model->amount . ' ' . $model->currencyCode->currency_sign;
				},
			],
			[
				'attribute' => 'total_nis',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) {
					if ($model->expenses_types_id == 1) {
						return '';
					}
					return $model->total_nis . '₪';
				},
			],
			'user_note',
			[
				'label' => 'Files',
				'vAlign' => 'middle',
				'format' => 'raw',
				'value' => function ($model, $key, $index, $widget) {
					if ($model->is_sys) {
						return '';
					}
					$files = count($model->files);
					return Html::a('<i class="fas fa-paperclip"></i> <span class="badge badge-light">'.$files.'</span>', [
						'trips/expenses-update-files',
						'trip_id' => $model->trips_id,
						'expense_id' => $model->id
					]);
				},
			],
			[
				'class' => '\kartik\grid\ActionColumn',
				'template' => '{update} {delete}',
				
				'buttons' => [
					'update' => function ($url, $model) use ($modelTrips) {
						if ($model->is_sys) {
							return Html::a('<span class="fas fa-pencil-alt"></span>', [
								'trips/expenses-update-eshel',
								'trip_id' => $modelTrips->id,
								'expense_id' => $model->id
							]);
						}
						return Html::a('<span class="fas fa-pencil-alt"></span>', [
							'trips/expenses-update',
							'trip_id' => $modelTrips->id,
							'expense_id' => $model->id
						]);
					},
					'delete' => function ($url, $model) use ($modelTrips) {
						return Html::a('<span class="fas fa-trash-alt"></span>', [
							'trips/expenses-delete',
							'trip_id' => $modelTrips->id,
							'expense_id' => $model->id
						]);
					},
				],

				'visibleButtons' => [
					'update' => function ($model, $key, $index) use ($modelTrips) {
						return ($model->is_sys == 0 && $modelTrips->canUpdateExpenses()) || ($modelTrips->canUpdateExpenses() && $model->expenses_types_id == 2);
					},
					
					'delete' => function ($model, $key, $index) use ($modelTrips) {
						return $model->is_sys == 0 && $modelTrips->canDeleteExpenses();
					},
					
				],

			],
		],

	]); ?>
	</div>
</div>