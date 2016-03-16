<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

use Cake\Filesystem\Folder;
use Cake\Filesystem\File;



require_once (ROOT .DS."webroot/functions.php");
require_once(ROOT .DS. 'vendor' . DS . 'TMDB' . DS . 'TMDB.php');
use TMDB\TMDB;

/**
 * Films Controller
 *
 * @property \App\Model\Table\FilmsTable $Films
 */
class FilmsController extends AppController
{

  public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    /**
     * Index method
     *
     * @return void
     */

     public function help()
{

}

// Pas utilisÃ© ! trop moche !!
public function indexSplash()
{
  $this->loadModel('Series');
  $this->loadModel('Music');

$films = $this->Films->find('all',array(
    //chaÃ®ne de caractÃ¨re ou tableau dÃ©finissant order
    'order' => array('Films.created DESC'),
    'limit' => 5));

    $series = $this->Series->find('all',array(
        //chaÃ®ne de renameFile()caractÃ¨re ou tableau dÃ©finissant order
        'order' => array('Series.created DESC'),
        'limit' => 5));

        $albums = $this->Music->find('all',array(
            //chaÃ®ne de caractÃ¨re ou tableau dÃ©finissant order
            'group' => array('Music.album'),
            'order' => array('Music.created DESC'),
            'limit' => 5));

    $this->set('albums', $albums);
    $this->set('series', $series);
    $this->set('films', $films);
    $this->set('_serialize', ['films']);
}

    public function indexAdmin()
    {
      $this->loadModel('Folders');
        $user_path = $this->Folders->findByType('Film_user')->first()['path'];
        $this->set('user_path', count(scandir($user_path))-2);

        $this->set('films', $this->paginate($this->Films));
        $this->set('_serialize', ['films']);
    }

    public function upload()
    {
      $this->loadModel('Folders');
        $user_path = $this->Folders->findByType('Film_user')->first()['path'];
        $this->set('user_path', count(scandir($user_path))-2);


      $this->loadModel('Folders');
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Film_upload')->first();

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
            $this->Flash->warning(__('Veuillez configurer un dossier d\'upload pour les films.'));
      }

      if ($this->request->is('post')) {

        $files = $this->request->data;
        $files_array = array();
        foreach ($files['file'] as $file) {

            $move = $path.'/'.$file['name'];
            $tmp_path = $file['tmp_name'];

            $filetype = $settings['filetype'];
            $ext = findExt($file['name'], $filetype);

            if($ext != false){
                $file_move = new File ($move, true, 0777);
                   unlink($move);
                // on move ! avec symlink !
                symlink($tmp_path, $move);
                chmod($move, 0777);
                array_push($files_array, $file);

              } else {
                $this->Flash->warning(__('Ceci n\'est pas un film!'));

              }

        }

        $this->set('files_up', $files_array);

      }
      $upload = '';
      $this->set('upload_dir', $path);
      $this->set(compact('upload'));
      $this->set('_serialize', ['upload']);
    }

    public function uploadUser()
    {

      $this->loadModel('Folders');
      $this->loadModel('Rmwords');


      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
      $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Film_user')->first();
      $filetype = $settings['filetype'];

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
            $this->Flash->warning(__('Veuillez configurer un dossier d\'upload pour les films.'));
      }

      if ($this->request->is('post')) {

          $files = $this->request->data;
          $files_array = array();

          foreach ($files['file'] as $file) {

            $year = findYear($file['name']);
            $ext = findExt($file['name'], $filetype);
            // Traitement avec les mots clÃ©s
            $name = rm_words($file['name'], $rm_end, $rm_start);

            $move = movePathModerate($path, $name, $year, $ext);
            $tmp_path = $file['tmp_name'];

            $filetype = $settings['filetype'];
            $ext = findExt($file['name'], $filetype);

            if($ext != false){
                $file_move = new File ($move, true, 0777);
                 unlink($move);
                // on move ! avec symlink !
                symlink($tmp_path, $move);
                chmod($move, 0777);
                array_push($files_array, $file);
              } else {
                $this->Flash->warning(__('Ceci n\'est pas un film!'));

              }
          }
          $this->set('files_up', $files_array);

      }
      $upload = '';
      $this->set('upload_dir', $path);
      $this->set(compact('upload'));
      $this->set('_serialize', ['upload']);
    }

// Pages visibles uniquement par les users (non identifiÃ©s)
    public function indexUser($view='grid', $genre='')
    {
      if ($view == 'grid') {
        $this->paginate = ['limit' => 16, 'order' => ['Films.created' => 'desc']];
      } else {
          if ($view == 'first') {
            $this->paginate = ['limit' => 16, 'order' => ['Films.created' => 'desc']];
          }else {
            $this->paginate = ['limit' => 5, 'order' => ['Films.created' => 'desc']];
      }
    }

      if(null != $this->request->query('search') || null != $this->request->query('year')){
        $year = $this->request->query('year');
        $search = $this->request->query('search');
        $this->set('films', $this->paginate($this->Films->find()->where([1,'AND' => [ 'OR' => ['titre_film LIKE' => '%'.$search.'%','real_film LIKE' => '%'.$search.'%','acteur_film LIKE' => '%'.$search.'%'],'annee_film LIKE' => '%'.$year.'%']])));
        $this->set('_serialize', ['films']);
        $this->set('search', $search);
        $this->set('year', $year);

      } else {
        $this->set('films', $this->paginate($this->Films->find()->where(['genre_film LIKE' => '%'.$genre.'%'])));
        $this->set('_serialize', ['films']);
      }
        $this->set('view', $view);
        $this->set('genre', $genre);

        $annees = $this->Films->find()->select(['annee_film'])->distinct('annee_film')->order(['annee_film' => 'DESC']);
        $years = array();
        foreach ($annees as $annee) {
          $year = substr($annee['annee_film'],0,4);
          if (!in_array($year, $years)) {
            array_push($years,$year);
          }

        }
        $this->set('annees', $years);

      }
//Renommage auto des fichiers et classement !
      public function renameFile($file='')
      {
        $file = str_replace('-dot-', '.', $file);
        $file = str_replace('-slash-', '/', $file);

        $this->loadModel('Folders');
        // on rÃ©cupÃ¨re les variables issues des autres controleurs
        $settings = $this->Folders->findByType('Films')->first();
        $path = $settings['path'];

        if(null != $this->request->query('symlink')){
          $new_file = $this->request->query('symlink');
          $file = $path.'/'.$file;
          $new_file = $path.'/'.$new_file;

          // On crÃ©Ã© le fichier avant > permet de crÃ©er les dossiers 20XX
          new File ($new_file, true, 0777);
          // on move ! avec symlink !
          if(symlink($file, $new_file)){
            chmod($new_file, 0777);
            $this->Flash->success(__('Le fichier a Ã©tÃ© renommÃ©.'));
            return $this->redirect(['action' => 'import']);

          }
        }
          $this->set('file', $file);
      }

// Affichage de la vue d'un film sÃ©lectionnÃ©
    public function viewUser($id = null)
    {
        $film = $this->Films->get($id, [
            'contain' => []
        ]);
        if ($this->request->is('post')) {
          $film->alert = 1;
          $this->Films->save($film);
          $this->Flash->warning(__('Le film Ã  Ã©tÃ© signalÃ© !'));
          $this->set('refer', 'javascript:history.go(-2)');
        }else {
        $this->set('refer', $this->referer());
        }

        if ($film->alert == 1) {
          $this->Flash->warning(__('Les informations concernant ce film ont Ã©tÃ©s signalÃ©es comme fausses !'));
        }
        $this->loadModel('Folders');
        $settings = $this->Folders->findByType('Films')->first();
        $path = $settings['path'];

        $size  = size($path.'/'.$film['file_film']);
        $actors = explode(', ', $film['acteur_film']);
        $real = explode(', ', $film['real_film']);


        $this->set('film', $film);
        $this->set('size', $size);
        $this->set('actors', $actors);
        $this->set('reals', $real);

        $this->set('_serialize', ['film']);
    }

// Download du fichier
    public function download($id = null)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_film')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $film = $this->Films->get($id, [
            'contain' => []
        ]);
        $file=$path."/".$film->file_film;
        $film->view += 1;
        $this->Films->save($film);

        $this->response->header('Location', $file );

    }

// GÃ©nÃ©ration du fichier m3u contenant le lien de stream
    public function stream($id = null)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_film')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $film = $this->Films->get($id, [
            'contain' => []
        ]);
        $file=$path."/".$film->file_film;
        $titre = $film->titre_film;
        $film->view += 1;
        $this->Films->save($film);

        $this->response->header([
                  'Content-Type: Application/m3u',
                  'Content-Disposition: inline'
              ]);
        $this->response->download($titre.'.m3u');
        echo "#EXTM3U\n
        #EXTINF:-1, $titre\n
        $file\n";
        $this->autoRender=false;

    }

// Permet de gÃ©nÃ©rer le stream pour faciliter la modÃ©ration des fichiers uploadÃ©s.
    public function moderateStream($file, $titre)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_film_mod')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $file=$path.'/'.$file;

        $this->response->header([
                  'Content-Type: Application/m3u',
                  'Content-Disposition: inline'
              ]);
        $this->response->download($titre.'.m3u');
        echo "#EXTM3U\n
        #EXTINF:-1, $titre\n
        $file\n";
        $this->autoRender=false;

    }

    /**
     * View method
     *
     * @param string|null $id Film id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $film = $this->Films->get($id, [
            'contain' => []
        ]);
        $this->set('film', $film);
        $this->set('_serialize', ['film']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */

     public function manualAdd($file='', $search='')
     {
       $this->loadModel('Config');

       $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];

       $tmdb = new TMDB($apikey, 'fr');
       $film = $this->Films->newEntity();
       $movies = $tmdb->searchMovie($search);
      $this->set('search', $search);

       // Si recherche
       if (null != $this->request->query('search') ){
         $movies = $tmdb->searchMovie($this->request->query('search'));
          $this->set('search', $this->request->query('search'));
      }


       // Si post !
     if (null != $this->request->query('tmdb') ) {



       $this->loadModel('Folders');
       // on rÃ©cupÃ¨re les variables issues des autres controleurs
       $settings = $this->Folders->findByType('Films')->first();

       if ($settings) {
           $path = $settings['path'];
       } else {
           $path = false;
           }

        $id_tmdb = $this->request->query('tmdb');
         $file =  str_replace('-slash-', '/', $file);
         $file =  str_replace('-dot-', '.', $file);
          $movie = $tmdb->getMovie($id_tmdb);

          //stockage dans la bdd :
          $id = $movie->getID();
          $titre = $movie->getTitle();
          $tagline = $movie->getTagline();
          $trailer = $movie->getTrailer();
          $resume = $movie->getOverview();
          $genre = $movie->getGenre();
          $real = $movie->getDirector();
          $acteur = $movie->getActor();
          $note = $movie->getVoteAverage();
          $annee = $movie->getYear();
          $time = $movie->getTime();

          $def = getDef($path.'/'.$file);
          $lang = getLang($path.'/'.$file);
          $sub = getSub($path.'/'.$file);
          downloadImg($tmdb->getImageURL('w185') . $movie->getPoster(), $movie->getID());


          $array = array(
            'tmdb' => $id,
            'titre_film' => $titre,
            'file_film' => $file,
            'annee_film' => $annee,
            'real_film' => $real,
            'genre_film' => $genre,
            'time_film' => $time,
            'resume_film' => $resume,
            'trailer_film' => $trailer,
            'acteur_film' => $acteur,
            'note_film' => $note,
            'tagline_film' => $tagline,
            'date_ajout' => '',
            'def_film' => $def,
            'audio' => $lang,
            'sub' => $sub);

            $film_add = $this->Films->newEntity($array, ['validate' => false]);
            if ($this->Films->save($film_add)) {
                $this->Flash->success(__('The film has been saved.'));
                return $this->redirect(['action' => 'import']);
            } else {
                $this->Flash->error(__('The film could not be saved. Please, try again.'));
            }


     }else {



       $id = array();
       $poster = array();
       $title = array();
       $year = array();
       $time = array();

       foreach ($movies as $movie) {

         $movie = $tmdb->getMovie($movie->getID());
         array_push($id, $movie->getID());
         array_push($poster, $tmdb->getImageURL('w92') . $movie->getPoster());
         array_push($title, $movie->getTitle());
         array_push($year, $movie->getYear());
         array_push($time, $movie->getTime());

       }
       $this->set('id', $id);
       $this->set('poster', $poster);
       $this->set('title', $title);
       $this->set('year', $year);
       $this->set('time', $time);

     }

         $this->set('file',$file);

         $this->set(compact('film'));
         $this->set('_serialize', ['film']);
     }

     public function error()
     {
       $this->loadModel('Folders');
         $user_path = $this->Folders->findByType('Film_user')->first()['path'];
         $this->set('user_path', count(scandir($user_path))-2);

       $this->set('alerts', $this->paginate($this->Films->findByAlert(true)));
       $this->set('trailers', $this->paginate($this->Films->findByTrailerFilm('')));
       $this->set('notes', $this->paginate($this->Films->findByNoteFilm('')));
       $this->set('annees', $this->paginate($this->Films->findByAnneeFilm('')));
       $this->set('times', $this->paginate($this->Films->findByTimeFilm('')));
     }



    public function add()
    {
        $film = $this->Films->newEntity();
        if ($this->request->is('post')) {
            $film = $this->Films->patchEntity($film, $this->request->data);
            if ($this->Films->save($film)) {
                $this->Flash->success(__('The film has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The film could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('film'));
        $this->set('_serialize', ['film']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Film id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $film = $this->Films->get($id, [
            'contain' => []
        ]);


        if ($this->request->is(['patch', 'post', 'put'])) {
            $film = $this->Films->patchEntity($film, $this->request->data);
            if ($this->Films->save($film)) {
                $this->Flash->success(__('The film has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The film could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('film'));
        $this->set('_serialize', ['film']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Film id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $film = $this->Films->get($id);
        if ($this->Films->delete($film)) {
            $this->Flash->success(__('The film has been deleted.'));
        } else {
            $this->Flash->error(__('The film could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }

    public function deleteFicheFile($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->loadModel('Folders');
        $settings = $this->Folders->findByType('Films')->first();
        $path = $settings['path'];



        $film = $this->Films->get($id);


        unlink($path.'/'.$film['file_film']);
        if ($this->Films->delete($film)) {
            $this->Flash->success(__('The film has been deleted.'));
        } else {
            $this->Flash->error(__('The film could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }

    public function import(){


      $this->loadModel('Folders');
      $this->loadModel('Rmwords');


      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Films')->first();
      $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
      $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
      $filetype = $settings['filetype'];

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
          $this->Session->setFlash(__('Veuillez configurer un dossier pour les films.'), 'flash_error');
          $this->redirect(array('controller' => 'settings', 'action' => 'index'));
      }
      $dir = new Folder($path);


      $films_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
      // on initialise les tableaux
      $films_name = array();
      $films_year = array();
      $films_path = array();
      $films_ok = array();

      foreach ($films_original as $film) {
        $film = str_replace($path.'/', '', $film);
        $film_path = $film;
        $year = findYear($film);
        $name = rm_words($film, $rm_end, $rm_start);



        if($this->Films->findByFileFilm($film_path)->first()['file_film']!=$film_path){
          // on push les data dans les tableaux
          array_push($films_path, $film_path);
          array_push($films_name, $name);
          array_push($films_year, $year);

          // on recup' l'api !
          $this->loadModel('Config');
          $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];

          $film_info = getFilm($name, $year, $film_path, $path, $apikey);

          if (is_array($film_info)){
            $film_add = $this->Films->newEntity($film_info, ['validate' => false]);
            $this->Films->save($film_add);
            array_push($films_ok, 'OK');
          } else {
            array_push($films_ok, 'Erreur');
          }


        }

      }
      if (count($films_path)==0){
        $this->Flash->success(__("Aucun film Ã  indexer !"));
      }else{
        $this->Flash->success(__("Fin du scan !"));
      }

      $this->set('films_path', $films_path);
      $this->set('films_name', $films_name);
      $this->set('films_year', $films_year);
      $this->set('films_ok', $films_ok);




    }

    public function rename(){

        $this->loadModel('Folders');
        $this->loadModel('Rmwords');

        // on rÃ©cupÃ¨re les variables issues des autres controleurs
        $settings = $this->Folders->findByType('Films')->first();
        $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
        $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
        $filetype = $settings['filetype'];

        if ($settings) {
            $path = $settings['path'];
        } else {
            $path = false;
            $this->Session->setFlash(__('Veuillez configurer un dossier pour les films.'), 'flash_error');
            $this->redirect(array('controller' => 'settings', 'action' => 'index'));
        }

        $settings2 = $this->Folders->findByType('Film_upload')->first();
        if ($settings2) {
            $path2 = $settings2['path'];
        } else {
            $path2 = false;
            $this->Session->setFlash(__('Veuillez configurer un dossier pour les films.'), 'flash_error');
            $this->redirect(array('controller' => 'settings', 'action' => 'index'));
        }

        $dir = new Folder($path2);

        $films_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
        // on initialise les tableaux
        $films_name = array();
        $films_year = array();
        $films_ext = array();
        $films_move = array();
        $films_path = array();
        $films_ok = array();

        foreach ($films_original as $film) {
          $film_path = $film;
          $film = str_replace($path2.'/', '', $film);
          $year = findYear($film);
          $ext = findExt($film, $filetype);
          $name = rm_words($film, $rm_end, $rm_start);

          $move = movePath($path, $name, $year, $ext);


          if($move != $film_path){
            // on push les data dans les tableaux
            array_push($films_path, $film_path);
            array_push($films_name, $name);
            array_push($films_year, $year);
            array_push($films_ext, $ext);
            array_push($films_move, $move);
            // On crÃ©Ã© le fichier avant > permet de crÃ©er les dossiers 20XX
            $file_move = new File ($move, true, 0777);
               unlink($move);
            // on move ! avec symlink !
            if(symlink($film_path, $move)){
              array_push($films_ok, 'OK');
              //chmod($move, 0777);
            }else {
              array_push($films_ok, 'Erreur');
            }

          }

        }
        if (count($films_path)==0){
          $this->Flash->success(__("Aucun film Ã  renomer !"));
        }else{
          $this->Flash->success(__("Fin du scan !"));
        }

        $this->set('films_path', $films_path);
        $this->set('films_name', $films_name);
        $this->set('films_year', $films_year);
        $this->set('films_ext', $films_ext);
        $this->set('films_move', $films_move);
        $this->set('films_ok', $films_ok);
    }

    public function moderate($act='', $file=''){

        $file = str_replace('-dot-','.',$file);
        $this->loadModel('Folders');
        $this->loadModel('Rmwords');
        $this->loadModel('Config');

        $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];
        $tmdb = new TMDB($apikey, 'fr');

        // on rÃ©cupÃ¨re les variables issues des autres controleurs
        $settings = $this->Folders->findByType('Film_user')->first();
        $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
        $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
        $filetype = $settings['filetype'];
        $path = $settings['path'];


        $settings2 = $this->Folders->findByType('Film_upload')->first();
        $path2 = $settings2['path'];

        if ($act == 'remove') {
            unlink($path.'/'.$file);
            $this->Flash->warning(__("Film rejetÃ©e !"));

        }

        if ($act == 'accept') {
            symlink($path.'/'.$file, $path2.'/'.$file);
            $this->Flash->success(__("Film dÃ©placÃ© vers Film_Upload !"));

        }


        $dir = new Folder($path);

        $films_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
        // on initialise les tableaux
        $films_file = array();
        $films_name = array();
        $films_year = array();
        $films_def = array();
        $films_audio = array();
        $films_sub = array();
        $films_size = array();
        $films_exist = array();



        foreach ($films_original as $film) {
          $def = getDef($film);
          $audio = getLang($film);
          $sub = getSub($film);
          $size = size($film);
          $film = str_replace($path, '', $film);
          $film = str_replace('/', '', $film);
          $film_file = $film;
          $name = rm_words($film, $rm_end, $rm_start);
          $year = findYear($film);



          // On cherche si l'episode existe dÃ©jÃ 
          $id_tmdb_search = $tmdb->searchMovie($name)[0]->getID();
          $id_tmdb = $this->Films->findByTmdb($id_tmdb_search)->first();
          if ($id_tmdb['id_tmdb']==$id_tmdb_search){
              $exist = $id_tmdb['id'];
            }else {
              $exist = false;
            }

            // On push les data dans les tableaux
            array_push($films_file, $film_file);
            array_push($films_name, $name);
            array_push($films_year, $year);
            array_push($films_def, $def);
            array_push($films_audio, $audio);
            array_push($films_sub, $sub);
            array_push($films_size, $size);
            array_push($films_exist, $exist);
          }
        if (count($films_file)==0){
          $this->Flash->success(__("Aucun film Ã  modÃ©rer !"));
        }else{
          $this->Flash->success(__("Fin du scan !"));
        }


        $this->set('films_file', $films_file);
        $this->set('films_name', $films_name);
        $this->set('films_year', $films_year);
        $this->set('films_def', $films_def);
        $this->set('films_sub', $films_sub);
        $this->set('films_size', $films_size);
        $this->set('films_audio', $films_audio);
        $this->set('films_exist', $films_exist);


    }

    public function manualEdit($file='')
    {
      $this->loadModel('Config');

      $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];
      $tmdb = new TMDB($apikey, 'fr');
      $film = $this->Films->newEntity();
      $file =  str_replace('-slash-', '/', $file);
      $file =  str_replace('-dot-', '.', $file);
      $id_fiche = $this->Films->findByFileFilm($file)->first();
      $search = $id_fiche['titre_film'];
      $movies = $tmdb->searchMovie($search);
     $this->set('search', $search);
      // Si recherche
      if (null != $this->request->query('search') ){
        $movies = $tmdb->searchMovie($this->request->query('search'));
         $this->set('search', $this->request->query('search'));
     }

    // Si post !
    if (null != $this->request->data('tmdb'))  {



      $this->loadModel('Folders');
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Films')->first();

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
          }

       $id_tmdb = $this->request->data('tmdb');
       $file =  str_replace('-slash-', '/', $file);
       $file =  str_replace('-dot-', '.', $file);
         $movie = $tmdb->getMovie($id_tmdb);

         //stockage dans la bdd :
         $id = $movie->getID();
         $titre = $movie->getTitle();
         $tagline = $movie->getTagline();
         $trailer = $movie->getTrailer();
         $resume = $movie->getOverview();
         $genre = $movie->getGenre();
         $real = $movie->getDirector();
         $acteur = $movie->getActor();
         $note = $movie->getVoteAverage();
         $annee = $movie->getYear();
         $time = $movie->getTime();

         $def = getDef($path.'/'.$file);
         $lang = getLang($path.'/'.$file);
         $sub = getSub($path.'/'.$file);
         downloadImg($tmdb->getImageURL('w185') . $movie->getPoster(), $movie->getID());


         $array = array(
           'tmdb' => $id,
           'titre_film' => $titre,
           'file_film' => $file,
           'annee_film' => $annee,
           'real_film' => $real,
           'genre_film' => $genre,
           'time_film' => $time,
           'resume_film' => $resume,
           'trailer_film' => $trailer,
           'acteur_film' => $acteur,
           'note_film' => $note,
           'tagline_film' => $tagline,
           'date_ajout' => '',
           'def_film' => $def,
           'audio' => $lang,
           'sub' => $sub);

          $film_add = $this->Films->patchEntity($id_fiche, $array, ['validate' => false]);
          //$this->Films->delete($id_fiche, ['atomic' => false]);

           if ($this->Films->save($film_add)) {
               $this->Flash->success(__('The film has been saved.'));
               return $this->redirect(['action' => 'indexUser']);
           } else {
               $this->Flash->error(__('The film could not be saved. Please, try again.'));
           }


    }else {



      $id = array();
      $poster = array();
      $title = array();
      $year = array();
      $time = array();

      foreach ($movies as $movie) {

        $movie = $tmdb->getMovie($movie->getID());
        array_push($id, $movie->getID());
        array_push($poster, $tmdb->getImageURL('w92') . $movie->getPoster());
        array_push($title, $movie->getTitle());
        array_push($year, $movie->getYear());
        array_push($time, $movie->getTime());

      }
      $this->set('id', $id);
      $this->set('poster', $poster);
      $this->set('title', $title);
      $this->set('year', $year);
      $this->set('time', $time);

    }

        $this->set(compact('film'));
        $this->set('_serialize', ['film']);
    }


}
