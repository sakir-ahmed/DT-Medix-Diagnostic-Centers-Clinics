<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Hr\IngestAttendancePunchesAction;
use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ZKTeco "ADMS" push protocol. The device is registered by serial number and
 * posts punches to /iclock/cdata. Unknown or disabled devices get a 404.
 */
final class ZkPushController extends Controller
{
    public function cdata(Request $request, IngestAttendancePunchesAction $ingest): Response
    {
        $device = $this->device($request);

        if ($request->isMethod('get')) {
            return $this->text(implode("\n", [
                'GET OPTION FROM: '.$device->serial_number,
                'ATTLOGStamp=None',
                'OPERLOGStamp=9999',
                'ATTPHOTOStamp=None',
                'ErrorDelay=30',
                'Delay=10',
                'TransTimes=00:00;14:00',
                'TransInterval=1',
                'TransFlag=TransData AttLog',
                'Realtime=1',
                'Encrypt=0',
            ]));
        }

        if (strtoupper((string) $request->query('table')) !== 'ATTLOG') {
            return $this->text('OK');
        }

        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', (string) $request->getContent()) ?: [] as $line) {
            $cells = explode("\t", trim($line));

            if (count($cells) >= 2 && $cells[0] !== '') {
                $rows[] = [$cells[0], $cells[1]];
            }
        }

        $result = $ingest->execute($device->branch_id, $rows, 'device', $device);

        return $this->text('OK: '.$result['created']);
    }

    public function getrequest(Request $request): Response
    {
        $this->device($request);

        return $this->text('OK');
    }

    public function devicecmd(Request $request): Response
    {
        $this->device($request);

        return $this->text('OK');
    }

    private function device(Request $request): AttendanceDevice
    {
        $device = AttendanceDevice::query()
            ->where('serial_number', (string) $request->query('SN'))
            ->where('is_active', true)
            ->first();

        abort_if($device === null, 404);

        $device->forceFill(['last_seen_at' => now()])->save();

        return $device;
    }

    private function text(string $body): Response
    {
        return response($body."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
