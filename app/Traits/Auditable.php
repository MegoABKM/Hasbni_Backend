<?php
namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 * @method static void created(\Closure|string|array $callback)
 * @method static void updated(\Closure|string|array $callback)
 * @method static void deleted(\Closure|string|array $callback)
 * @property int|string|null $id
 */
trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            $model->logAudit('created');
        });

        static::updated(function (Model $model) {
            $model->logAudit('updated');
        });

        static::deleted(function (Model $model) {
            $model->logAudit('deleted');
        });
    }

    /**
     * @param string $event
     */
    protected function logAudit(string $event)
    {
        $oldValues = [];
        $newValues = [];

        // استبعاد الحقول التي تتغير باستمرار ولا تؤثر محاسبياً لتوفير المساحة
        $ignored = ['created_at', 'updated_at', 'remember_token'];

        if ($event === 'created') {
            $newValues = Arr::except($this->getAttributes(), $ignored);
        } elseif ($event === 'updated') {
            $changes = Arr::except($this->getChanges(), $ignored);
            
            // إذا لم يتغير شيء محاسبي (مثلاً تغير الوقت فقط)، لا تسجل شيء لتوفير الـ RAM
            if (empty($changes)) return; 

            $newValues = $changes;
            foreach ($changes as $key => $value) {
                $oldValues[$key] = $this->getOriginal($key);
            }
        } elseif ($event === 'deleted') {
            $oldValues = Arr::except($this->getAttributes(), $ignored);
        }

        // الحفظ مباشرة في قاعدة البيانات
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->id,
            'old_values' => empty($oldValues) ? null : json_encode($oldValues),
            'new_values' => empty($newValues) ? null : json_encode($newValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}