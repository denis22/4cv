<?php
/**
 * 
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\console\widgets\Table;

use yii\helpers\ArrayHelper;


use kartik\mpdf\Pdf;

use app\models\UsersAuth;
use app\models\Users;
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

/**
 *
 */
class ReportController extends Controller
{
	/**
	 * Send report to HR
	 * @return int Exit code
	 */
	public function actionHr()
	{
		$trips = [];
		$now = new \DateTime();
		$now->modify('-1 month');
		$flights = Flights::find()->where(['>=', 'departure_date', $now->format('Y-m-01')])->orderBy('`trips_id`, `users_id`, `departure_date`, `departure_time`')->all();
		if ($flights) {
			$html = '<h1>No flights</h1>';
			foreach ($flights as $key => $flight) {
				if ($flight->from_country == 123 || $flight->to_country == 123) {
					if (empty($trips[$flight->trips_id])) {
						$trips[$flight->trips_id]['departure'] = '';
						$trips[$flight->trips_id]['arrival'] = '';
						$trips[$flight->trips_id]['from'] = '';
						$trips[$flight->trips_id]['to'] = '';
					}
					$trips[$flight->trips_id]['users_id'] = $flight->users_id;
					$trips[$flight->trips_id]['full_name'] = $flight->user->full_name;
					if ($flight->from_country == 123) {
						$trips[$flight->trips_id]['departure'] = Yii::$app->formatter->format($flight->departure_date, 'date') . ' ' . Yii::$app->formatter->format($flight->departure_time, 'time');
						$trips[$flight->trips_id]['from'] = $flight->fromCountry->country_en . ' - ' . $flight->toCountry->country_en;
					} elseif ($flight->to_country == 123) {
						$trips[$flight->trips_id]['arrival'] = Yii::$app->formatter->format($flight->departure_date, 'date') . ' ' . Yii::$app->formatter->format($flight->departure_time, 'time');
						$trips[$flight->trips_id]['to'] = $flight->fromCountry->country_en . ' - ' . $flight->toCountry->country_en;
					}
				}
			}
			$html = $this->renderPartial('@app/views/api/pdf', [
				'trips' => $trips,
			]);

			$filename = Yii::getAlias('@runtime/EVTravel_HR_Report_-_' . $now->format('F\_\o\f\_Y') . '.pdf');
			$title = 'EV Travel: Report for HR, ' . $now->format('F \o\f Y');
			$pdf = new Pdf([
				'mode' => Pdf::MODE_UTF8, // leaner size using standard fonts
				'format' => Pdf::FORMAT_A4,
				'orientation' => Pdf::ORIENT_LANDSCAPE,
				'destination' => Pdf::DEST_FILE,
				'filename' => $filename,
				'content' => $html,
				'options' => [
					'title' => $title,
				],
				'methods' => [
					'SetHeader' => ['EGMO & Vargus: EV Travel Report for HR, ' . $now->format('F \o\f Y') . '||Generated On: '.Yii::$app->formatter->format('now', 'datetime')],
					'SetFooter' => ['|Page {PAGENO}|'],
				]
			]);

			$pdf->render();

			$mail = Yii::$app->mailer
				->compose()
				->setFrom(Yii::$app->params['notifyEmail'])
				->setTo(Yii::$app->params['treasurer_approve_notify'])
				->setSubject($title)
				->setTextBody('The report is attached to the letter.')
				->attach($filename)
				->send();

			@unlink($filename);
		}

		return ExitCode::OK;
	}
}
