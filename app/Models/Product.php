namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // أضف هذا

class Product extends Model {
    use SoftDeletes; // أضف هذا
    protected $guarded = [];
}