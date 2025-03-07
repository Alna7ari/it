<?php
namespace Phpanonymous\It\Controllers\Baboon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Phpanonymous\It\Controllers\Baboon\BaboonDataTable;
use Phpanonymous\It\Controllers\Baboon\BaboonShowPage;
use Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonDeleteModule;
use Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonModule;
use Phpanonymous\It\Controllers\Baboon\MasterBaboon as Baboon;
use Phpanonymous\It\Controllers\Baboon\Statistics;

class Home extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function index() {

		if (!empty(request('delete_module'))) {
			// Delete .baboon Text CRUD by request('delete_module')
			return (new BaboonDeleteModule)->init();
		}

		$data = [];

		$baboonModule = (new \Phpanonymous\It\Controllers\Baboon\CurrentModuleMaker\BaboonModule);
		// Load all Modules
		$getAllModule = $baboonModule->getAllModules();

		$data['getAllModule'] = $getAllModule;
		if (!empty(request('module')) && !is_null(request('module'))) {
			$Modulefile = 'baboon/' . request('module');
			// Edit Modules
			$readmodule = $baboonModule->read($Modulefile);
			if ($readmodule === false) {
				// redirect if fails load Files
				header('Location: ' . url('it/baboon-sd'));
				exit;
			} else {
				$data['module_data'] = $readmodule;
				$data['module_last_modified'] = date('Y-m-d h:i:s A T', $baboonModule->lastModified($Modulefile));
			}
		} else {
			$data['module_data'] = null;
			$data['module_last_modified'] = null;
		}

		app()->singleton('module_data', function () use ($data) {
			return $data['module_data'];
		});

		$data['title'] = it_trans('it.baboon-sd');
		//return $data;
		return view('baboon.home', $data);
	}

	public function index_post(Request $r) {
		$this->validate(request(), [
			'project_title' => 'required',
			'controller_name' => 'required',
			'controller_namespace' => 'required',
			'model_name' => 'required',
			'model_namespace' => 'required',
			'lang_file' => 'required',
			'col_name' => 'required',
			'col_type' => 'required',
			'col_name_convention' => 'required',
		], [], [
			'model_name' => it_trans('it.model_name'),
			'project_title' => it_trans('it.project_title'),
			'controller_name' => it_trans('it.controller_name'),
			'lang_file' => it_trans('it.lang_file'),
			'col_name' => it_trans('it.col_name'),
			'col_type' => it_trans('it.col_type'),
			'col_name_convention' => it_trans('it.col_name_convention'),
			'controller_namespace' => it_trans('it.controller_namespace'),
			'model_namespace' => it_trans('it.model_namespace'),
			'model_name' => it_trans('it.model_name'),
		]);

		// Create .baboon Text CRUD
		$prepare_module = new BaboonModule();
		$prepare_module->init();
		$module = $prepare_module->getmodule_data();

		// Check or Make Pathes
		$path = $this->makeAndCheckPaths();

		if ($path['controller_path'] && $path['model_path'] && $path['database_path']) {

			// Make Controller And ApiController
			$this->makeControllerAndApi();

			// Make Model
			$this->makeModel();

			// Make Views Blade File
			$this->makeViews();

			// Make Datatable And Validation Rules
			$this->makeDataTableAndValidation();

			// Make Language Files And Migrate Files
			$this->makeLangAndMigrate($module);

		}

		\Config::set('filesystems.default', 'it');

		// Make Admin Routes And Api Routes
		$this->makeRoutes();

		// Make Menu
		$this->makeMenu();

		if (!empty(request('collect'))) {
			(new Statistics)->init();
		}

		return response(['status' => true, 'message' => 'Module - CRUD Generated'], 200);
	}

/* Posts Method From index_post Start */

	public static function autoconvSchemaTableName($conv) {
		if (!in_array(substr($conv, -1), ['s'])) {
			if (substr($conv, -1) == 'y') {
				$conv = substr($conv, 0, -1) . 'ies';
			} else {
				$conv = $conv . 's';
			}
		}
		return $conv;
	}

	public function makeMenu() {
		$link = strtolower(preg_replace('/Controller|controller/i', '', request('controller_name')));
		//********* Preparing Menu List ***********/
		$admin_menu = file_get_contents(base_path('resources/views/admin/layouts/menu.blade.php'));
		$fa_icon = !empty(request('fa_icon')) ? request('fa_icon') : 'fa fa-icons';
		if (!preg_match("/" . $link . "/i", $admin_menu)) {
			$link2 = '{{active_link(\'' . $link . '\',\'menu-open\')}} ';
			$link3 = '{{active_link(\'\',\'active\')}}';
			$link4 = '{{active_link(\'' . $link . '\',\'active\')}}';
			$link5 = '{{trans(\'' . request('lang_file') . '.' . $link . '\')}} ';
			$urlurl = '{{aurl(\'' . $link . '\')}}';
			$title = '{{trans(\'' . request('lang_file') . '.' . $link . '\')}} ';
			$create = '{{trans(\'' . request('lang_file') . '.create\')}} ';

			$newmenu = '@if(admin()->user()->role("' . $link . '_show"))' . "\r\n";
			$newmenu .= '<li class="nav-item ' . $link2 . '">' . "\r\n";
			$newmenu .= '  <a href="#" class="nav-link ' . $link4 . '">' . "\r\n";
			$newmenu .= '    <i class="nav-icon ' . $fa_icon . '"></i>' . "\r\n";
			$newmenu .= '    <p>' . "\r\n";
			$newmenu .= '      ' . $title . '' . "\r\n";
			$newmenu .= '      <i class="right fas fa-angle-left"></i>' . "\r\n";
			$newmenu .= '    </p>' . "\r\n";
			$newmenu .= '  </a>' . "\r\n";
			$newmenu .= '  <ul class="nav nav-treeview">' . "\r\n";
			$newmenu .= '    <li class="nav-item">' . "\r\n";
			$newmenu .= '      <a href="' . $urlurl . '" class="nav-link  ' . $link4 . '">' . "\r\n";
			$newmenu .= '        <i class="' . $fa_icon . ' nav-icon"></i>' . "\r\n";
			$newmenu .= '        <p>' . $title . '</p>' . "\r\n";
			$newmenu .= '      </a>' . "\r\n";
			$newmenu .= '    </li>' . "\r\n";
			$newmenu .= '    <li class="nav-item">' . "\r\n";
			$newmenu .= '      <a href="{{ aurl(\'' . $link . '/create\') }}" class="nav-link">' . "\r\n";
			$newmenu .= '        <i class="fas fa-plus nav-icon"></i>' . "\r\n";
			$newmenu .= '        <p>' . $create . '</p>' . "\r\n";
			$newmenu .= '      </a>' . "\r\n";
			$newmenu .= '    </li>' . "\r\n";
			$newmenu .= '  </ul>' . "\r\n";
			$newmenu .= '</li>' . "\r\n";
			$newmenu .= '@endif' . "\r\n";
			\Storage::put('resources/views/admin/layouts/menu.blade.php', $admin_menu . "\r\n" . $newmenu);
		}
		//********* Preparing Menu List ***********/
	}

	public function makeRoutes() {
		//********* Preparing Route Admin ***********/
		$link = strtolower(preg_replace('/Controller|controller/i', '', request('controller_name')));
		$end_route = '////////AdminRoutes/*End*///////////////';
		$namespace_single = explode('App\Http\Controllers\\', request('controller_namespace'))[1];
		$route1 = 'Route::resource(\'' . $link . '\',\'' . $namespace_single . '\\' . request('controller_name') . '\'); ' . "\r\n";
		$route2 = '		Route::post(\'' . $link . '/multi_delete\',\'' . $namespace_single . '\\' . request('controller_name') . '@multi_delete\'); ' . "\r\n";
		// Dropzone Route Start//
		$route3 = '';
		foreach (request('col_type') as $col_type) {
			if ($col_type == 'dropzone') {
				$route3 .= '		Route::post(\'' . $link . '/upload/multi\',\'' . $namespace_single . '\\' . request('controller_name') . '@multi_upload\'); ' . "\r\n";
				$route3 .= '		Route::post(\'' . $link . '/delete/file\',\'' . $namespace_single . '\\' . request('controller_name') . '@delete_file\'); ' . "\r\n";
			}
		}
		// Dropzone Route End//
		$admin_routes = file_get_contents(base_path('routes/admin.php'));

		if (!preg_match("/" . $link . "/i", $admin_routes)) {
			$admin_routes = str_replace($end_route, $route1 . $route2 . $route3 . "		" . $end_route, $admin_routes);
			\Storage::put('routes/admin.php', $admin_routes);
		}
		// Linked With Ajax Route Start//
		$route3 = '';
		$xi = 0;
		foreach (request('col_name_convention') as $input_ajax) {
			if (!empty(request('link_ajax' . $xi)) && request('link_ajax' . $xi) == 'yes') {
				$explode_name_ajax = explode('|', $input_ajax);
				$col_name_ajax = count($explode_name_ajax) > 0 ? $explode_name_ajax[0] : $input_ajax;
				$route3 = 'Route::post(\'' . $link . '/get/' . str_replace('_', '/', $col_name_ajax) . '\',\'' . $namespace_single . '\\' . request('controller_name') . '@get_' . $col_name_ajax . '\'); ' . "\r\n";
				if (!preg_match("/" . request('controller_name') . "@get_" . $col_name_ajax . "/i", $admin_routes)) {
					$admin_routes = str_replace($end_route, $route3 . "		" . $end_route, $admin_routes);
					\Storage::put('routes/admin.php', $admin_routes);
				}
			}
			$xi++;
		}
		// Linked With Ajax Route End//
		//********* Preparing Route ***********/
		//********* Preparing Route Api ***********/
		$linkapi = strtolower(preg_replace('/Controller|controller/i', '', request('controller_name'))) . 'Api';
		$end_routeapi = '//////// Api Routes /* End */ //////////////';
		$namespace_singleapi = 'Api';
		$route1 = 'Route::resource(\'' . $linkapi . '\',\'' . $namespace_singleapi . '\\' . request('controller_name') . '\'); ' . "\r\n";
		$routeapi = 'Route::post(\'' . $linkapi . '/multi_delete\',\'' . $namespace_singleapi . '\\' . request('controller_name') . '@multi_delete\'); ' . "\r\n";

		$api_routes = file_get_contents(base_path('routes/api.php'));
		if (!preg_match("/" . $link . "/i", $api_routes)) {
			$api_routes = str_replace($end_routeapi, $route1 . $routeapi . "		" . $end_routeapi, $api_routes);
			\Storage::put('routes/api.php', $api_routes);
		}
		//********* Preparing Route End Api ***********/
	}

	public function makeLangAndMigrate($module) {
		$migrate = Baboon::migrate(request());

		////////////////// Language Files ////////////////////
		$lang_ar = Baboon::Makelang(request());
		Baboon::write($lang_ar, request('lang_file'), 'resources\\lang\\ar\\');

		if (is_dir(base_path('resources/lang/en'))) {
			$lang_en = Baboon::Makelang(request(), 'en');
			Baboon::write($lang_en, request('lang_file'), 'resources\\lang\\en\\');
		}

		if (is_dir(base_path('resources/lang/fr'))) {
			$lang_fr = Baboon::Makelang(request(), 'fr');
			Baboon::write($lang_fr, request('lang_file'), 'resources\\lang\\fr\\');
		}
		////////////////// Language Files ////////////////////
		if (request()->has('make_migration')) {
			Baboon::write($migrate, $module->migration_file_name, 'database\\migrations');
			// Disable ForignKey Checks DB
			if (request()->has('auto_migrate')) {
				\DB::statement('SET FOREIGN_KEY_CHECKS = 0');

				\DB::table('migrations')
					->where('migration', $module->migration_file_name)
					->delete();
				\Schema::dropIfExists($module->convention_name);
				\Artisan::call('migrate', []);
				if (\DB::table('migrations')->where('migration', $module->migration_file_name)->count() == 0) {
					\DB::table('migrations')->create([
						'migration' => $module->migration_file_name,
						'batch' => @\DB::table('migrations')->orderBy('id', 'desc')->first() + 1,
					]);
				}
				// Enable ForignKey Checks DB
				\DB::statement('SET FOREIGN_KEY_CHECKS = 1');
			}
		}
	}

	public function makeModel() {
		if (request()->has('make_model')) {
			$model = Baboon::makeModel(request('model_namespace'), request('model_name'));
			Baboon::write($model, request('model_name'), request('model_namespace'));
		}
	}

	public function makeControllerAndApi() {
		if (request()->has('make_controller')) {
			$controller = Baboon::makeController(request(), request('controller_namespace'),
				request('model_namespace') . '\\' . request('model_name'),
				request('controller_name'));

			Baboon::write($controller, request('controller_name'), request('controller_namespace'));
		}

		// if (request()->has('make_controller_api')) {
		$controllerApi = Baboon::makeControllerApi(request(), request('controller_namespace'),
			request('model_namespace') . '\\' . request('model_name'),
			request('controller_name'));

		Baboon::write($controllerApi, request('controller_name') . 'Api', 'App\Http\Controllers/Api');

		// }
	}

	public function makeDataTableAndValidation() {
		$folder2 = str_replace('Controller', '', request('controller_name'));
		if (request()->has('make_datatable')) {
			Baboon::write(BaboonDataTable::dbclass(request()), $folder2 . 'DataTable', 'app\\DataTables\\');
		}

		Baboon::write(BaboonValidations::validationClass(request()), $folder2 . 'Request', 'app\\Http\\Controllers\\Validations\\');
		// Admin Route List Roles Start//
		$routes = Baboon::RouteListRoles(request());
		Baboon::write($routes, 'AdminRouteList', 'app\\Http\\');
		// Admin Route List Roles End//
	}

	public function makeViews() {

		if (request()->has('make_views')) {
			$view = Baboon::inputsCreate(request());
			$view_update = Baboon::inputsUpdate(request());
			$view_index = Baboon::IndexBlade(request());
			$show_page = BaboonShowPage::show(request());
			$action = Baboon::actions(request());

			$blade_name = str_replace('controller', '', strtolower(request('controller_name')));
			if (!empty($view)) {
				Baboon::check_path(request('admin_folder_path') . '\\' . $blade_name);
				Baboon::write($view, 'create.blade', request('admin_folder_path') . '\\' . $blade_name);
			}

			if (!empty($show_page)) {
				Baboon::check_path(request('admin_folder_path') . '\\' . $blade_name);
				Baboon::write($show_page, 'show.blade', request('admin_folder_path') . '\\' . $blade_name);
			}

			if (!empty($view_update)) {
				Baboon::check_path(request('admin_folder_path') . '\\' . $blade_name);
				Baboon::write($view_update, 'edit.blade', request('admin_folder_path') . '\\' . $blade_name);
			}

			Baboon::check_path(request('admin_folder_path') . '\\' . $blade_name . '\\buttons');
			Baboon::write($view_index, 'index.blade', request('admin_folder_path') . '\\' . $blade_name); // Make Index Blade File

			Baboon::write($action, 'actions.blade', request('admin_folder_path') . '\\' . $blade_name . '\\buttons'); // Make action buttons Blade
		}
	}

	public function makeAndCheckPaths() {

		$model_path = Baboon::check_path(request('model_namespace'));
		// Make NameSpace Folder Models
		$controller_path = Baboon::check_path(request('controller_namespace')); // Make Namespace folder Controller
		$database_path = Baboon::check_path('database\\migrations');
		// Make database folder

		Baboon::check_path('app\\Http\\Controllers\\Validations');
		// Make Validations folder
		Baboon::check_path('app\\DataTables'); // Make DataTables folder
		Baboon::check_path(request('admin_folder_path')); // Make views folder
		Baboon::check_path('app\\Http\\Controllers\\Api'); // Make assets folder
		return [
			'model_path' => $model_path,
			'controller_path' => $controller_path,
			'database_path' => $database_path,
		];
	}
/* Posts Method From index_post End*/

// Make Namespace From Ajax Request
	public function makeNamespace($type) {
		if (request()->has('namespace')) {
			if ($type == 'controller') {
				\Storage::disk('it')
					->makeDirectory('app/Http/Controllers/' . str_replace('\\', '/', request('namespace')));
			}
		}
	}

}
