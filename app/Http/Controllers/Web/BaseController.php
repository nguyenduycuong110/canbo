<?php  
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Traits\Loggable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller{

    protected $baseRedirect = 'dashboard.index';
    protected $route;
    protected $namespace;
    protected $nestedset;

    private $service;

    use Loggable;

    public function __construct(
        $service,
    ){
       $this->service = $service;
    }

    protected function getData(): array{
        return [];
    }

    public function index(Request $request): View | RedirectResponse{
        try {
            $records = $this->service->paginate($request);
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            $data = $this->getData();
            extract($data);
            return view("backend.{$this->namespace}.index", compact(
                'records',
                'config',
                ...array_keys($data)
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function baseSave(Request $request, ?int $id = null): RedirectResponse{
        try {
            if($response = $this->service->save($request, $id)){
                flash()->success(Lang::get('message.save_success'));
                return redirect()->route("{$this->route}.index");
            }else{
                flash()->error(Lang::get('message.save_failed'));
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function create(Request $request){
        try {
            $config = $this->config();
            $config['user_id'] = Auth::user()->id;
            $config['method'] = 'create';
            $data = $this->getData();
            extract($data);
            return view("backend.{$this->namespace}.save", compact(
                'config',
                ...array_keys($data)
            ));
        }catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
            return redirect()->route("{$this->route}.index");
        }catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
        
    }

    public function edit(Request $request, int $id) : View | RedirectResponse{
        try {
            $model = $this->service->findById($id);
            $config = $this->config();
            $data = $this->getData();
            extract($data);
            $config['method'] = 'update';
            return view("backend.{$this->namespace}.save", compact(
                'config',
                'model',
                ...array_keys($data)
            ));

        } catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
           return redirect()->route("{$this->route}.index");
        }
         catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function delete(Request $request, $id): View | RedirectResponse {
        try {
            $model = $this->service->findById($id);
            $config = $this->config();
            return view("backend.{$this->namespace}.delete", compact(
                'model',
                'config'
            ));

        } catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
            return redirect()->route("{$this->route}.index");
        }
         catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse{
        try {
           
            $response = $this->service->destroy($id);
            flash()->success(Lang::get('message.delete_success'));
            return redirect()->route("{$this->route}.index");


        } catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
            return redirect()->route("{$this->route}.index");
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    protected function config(){
        return [
            'js' => [
                'backend/js/plugins/switchery/switchery.js',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                'backend/plugins/ckfinder_2/ckfinder.js',
                'backend/library/finder.js',
                // 'backend/library/location.js'
            ],
            'css' => [
                'backend/css/plugins/switchery/switchery.css',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
            ],
            'route' => $this->route
        ];
    }

    

}