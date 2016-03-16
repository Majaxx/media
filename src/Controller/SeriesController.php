<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

use Cake\Filesystem\Folder;
use Cake\Filesystem\File;$file_move = new File ($move, true, 0777);




require_once (ROOT .DS."webroot/functions.php");
require_once(ROOT .DS. 'vendor' . DS . 'TMDB' . DS . 'TMDB.php');
use TMDB\TMDB;


/**
 * Series Controller
 *
 * @property \App\Model\Table\SeriesTable $Series
 */
class SeriesController extends AppController
{

  public function help()
  {

  }


    /**
     * Index method
     *
     * @return void
     */
    public function indexAdmin()
    {
      $this->loadModel('Folders');
        $user_path = $this->Folders->findByType('Serie_user')->first()['path'];
        $this->set('user_path', count(scandir($user_path))-2);

        $this->set('series', $this->paginate($this->Series));
        $this->set('_serialize', ['series']);
    }


    public function indexUser($view='grid')
    {

      if ($view == 'grid') {
        $this->paginate = ['limit' => 16, 'order' => ['Series.created' => 'desc']];
      } else {
          if ($view == 'first') {
            $this->paginate = ['limit' => 16, 'order' => ['Series.created' => 'desc']];
          }else {
            $this->paginate = ['limit' => 5, 'order' => ['Series.created' => 'desc']];
      }
    }

      if(null != $this->request->query('search')){
        $search = $this->request->query('search');
          $this->set('series', $this->paginate($this->Series->find()->where([1,'OR' => ['titre LIKE' => '%'.$search.'%','annee LIKE' => '%'.$search.'%']])->distinct('id_tmdb')));
        $this->set('_serialize', ['films']);
        $this->set('search', $search);

      } else {
        $this->set('series', $this->paginate($this->Series->find()->distinct('id_tmdb')));
        $this->set('_serialize', ['series']);
      }


        $this->set('view', $view);

    }

    public function renameFile($file='')
    {
      $file = str_replace('-dot-', '.', $file);
      $file = str_replace('-slash-', '/', $file);

      $this->loadModel('Folders');
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Series')->first();
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


    public function error()
    {
      $this->loadModel('Folders');
        $user_path = $this->Folders->findByType('Serie_user')->first()['path'];
        $this->set('user_path', count(scandir($user_path))-2);
      $this->set('alerts', $this->paginate($this->Series->findByAlert(true)));
      $this->set('titres', $this->paginate($this->Series->findByTitreEpisode('')));
      $this->set('audios', $this->paginate($this->Series->findByAudio('')));
      $this->set('defs', $this->paginate($this->Series->findByDef('')));
      $this->set('subs', $this->paginate($this->Series->findBySub('')));
      $this->set('notes', $this->paginate($this->Series->findByNote('')));


    }


    public function upload()
    {
      $this->loadModel('Folders');
        $user_path = $this->Folders->findByType('Serie_user')->first()['path'];
        $this->set('user_path', count(scandir($user_path))-2);

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Serie_upload')->first();

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
                $this->Flash->warning(__('Ceci n\'est pas une sÃ©rie!'));

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
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Serie_user')->first();

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
            $this->Flash->warning(__('Veuillez configurer un dossier d\'upload pour les sÃ©ries.'));
      }

      if ($this->request->is('post')) {

          $files = $this->request->data;
          $files_array = array();
          foreach ($files['file'] as $file) {

              $move = $path.'/'.str_replace(" ", ".", $file['name']);
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
                  $this->Flash->warning(__('Ceci n\'est pas une sÃ©rie!'));

                }

          }

          $this->set('files_up', $files_array);

      }
      $upload = '';
      $this->set('upload_dir', $path);
      $this->set(compact('upload'));
      $this->set('_serialize', ['upload']);
    }



    public function viewUser($id = null)
    {
        $serie = $this->Series->get($id, [
            'contain' => []
        ]);
        if ($this->request->is('post')) {
          $serie->alert = 1;
          $this->Series->save($serie);
          $this->Flash->warning(__('La sÃ©rie Ã  Ã©tÃ© signalÃ©e !'));
          $this->set('refer', 'javascript:history.go(-2)');
        }else {
        $this->set('refer', $this->referer());
        }

        if ($serie->alert == 1) {
          $this->Flash->warning(__('Les informations concernant cette sÃ©rie ont Ã©tÃ©s signalÃ©es comme fausses !'));
        }

        $episodes = $this->Series->findByIdTmdb($serie->id_tmdb)->order(['season','episode']);
        $seasons = $this->Series->findByIdTmdb($serie->id_tmdb)->select(['season'])->distinct('season')->order(['season' => 'DESC']);


        $this->set('serie', $serie);
        $this->set('episodes', $episodes);
        $this->set('seasons', $seasons);


        $this->set('_serialize', ['serie']);
    }


    /**
     * View method
     *
     * @param string|null $id Series id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $series = $this->Series->get($id, [
            'contain' => []
        ]);
        $this->set('series', $series);
        $this->set('_serialize', ['series']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $series = $this->Series->newEntity();
        if ($this->request->is('post')) {
            $series = $this->Series->patchEntity($series, $this->request->data);
            if ($this->Series->save($series)) {
                $this->Flash->success(__('The series has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The series could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('series'));
        $this->set('_serialize', ['series']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Series id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $series = $this->Series->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $series = $this->Series->patchEntity($series, $this->request->data);
            if ($this->Series->save($series)) {
                $this->Flash->success(__('The series has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The series could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('series'));
        $this->set('_serialize', ['series']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Series id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $series = $this->Series->get($id);
        if ($this->Series->delete($series)) {
            $this->Flash->success(__('The series has been deleted.'));
        } else {
            $this->Flash->error(__('The series could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'indexAdmin']);
    }


    public function rename(){

        $this->loadModel('Folders');
        $this->loadModel('Rmwords');

        // on rÃ©cupÃ¨re les variables issues des autres controleurs
        $settings = $this->Folders->findByType('Series')->first();
        $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
        $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
        $filetype = $settings['filetype'];

        if ($settings) {
            $path = $settings['path'];
        } else {
            $path = false;
            $this->Session->setFlash(__('Veuillez configurer un dossier pour les sÃ©ries.'), 'flash_error');
            $this->redirect(array('controller' => 'settings', 'action' => 'index'));
        }

        $settings2 = $this->Folders->findByType('Serie_upload')->first();
        if ($settings2) {
            $path2 = $settings2['path'];
        } else {
            $path2 = false;
            $this->Session->setFlash(__('Veuillez configurer un dossier pour les films.'), 'flash_error');
            $this->redirect(array('controller' => 'settings', 'action' => 'index'));
        }

        $dir = new Folder($path2);

        $series_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
        // on initialise les tableaux
        $series_name = array();
        $series_ext = array();
        $series_move = array();
        $series_path = array();
        $series_ok = array();
        $series_episode = array();
        $series_season = array();


        foreach ($series_original as $serie) {
          $serie_path = $serie;
          $serie = str_replace($path2, '', $serie);
          $ext = findExt($serie, $filetype);
          $name = rm_words($serie, $rm_end, $rm_start, '1');
          $season = findSeason($serie);
          $episode = findEpisode($serie);
          $move = movePathSerie($path, $name, $season, $episode, $ext);



          if($move != $serie_path){
            // on push les data dans les tableaux
            array_push($series_path, $serie_path);
            array_push($series_name, $name);
            array_push($series_season, $season);
            array_push($series_episode, $episode);
            array_push($series_ext, $ext);
            array_push($series_move, $move);


            // On crÃ©Ã© le fichier avant > permet de crÃ©er les dossiers 20XX
            $file_move = new File ($move, true, 0777);
			unlink($move);
            // on move ! avec symlink !
            if(symlink($serie_path, $move)){
              array_push($series_ok, 'OK');
              //chmod($move, 0777);
            }else {
              array_push($series_ok, 'Erreur');
            }

          }

        }
        if (count($series_path)==0){
          $this->Flash->success(__("Aucune sÃ©rie Ã  renomer !"));
        }else{
          $this->Flash->success(__("Fin du scan !"));
        }

        $this->set('series_path', $series_path);
        $this->set('series_name', $series_name);
        $this->set('series_season', $series_season);
        $this->set('series_episode', $series_episode);
        $this->set('series_ext', $series_ext);
        $this->set('series_move', $series_move);
        $this->set('series_ok', $series_ok);

    }


    public function import(){


      $this->loadModel('Folders');
      $this->loadModel('Rmwords');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Series')->first();
      $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
      $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
      $filetype = $settings['filetype'];

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
          $this->Session->setFlash(__('Veuillez configurer un dossier pour les sÃ©ries.'), 'flash_error');
          $this->redirect(array('controller' => 'settings', 'action' => 'index'));
      }

      $dir = new Folder($path);


      $series_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
      // on initialise les tableaux
      $series_name = array();
      $series_path = array();
      $series_episode = array();
      $series_season = array();
      $series_ok = array();

      $name_old = "";
      $info_old = array();

      foreach ($series_original as $serie) {
        $serie = str_replace($path.'/', '', $serie);
        $serie_path = $serie;

        $ext = findExt($serie, $filetype);
        $name = rm_words($serie, $rm_end, $rm_start, '1');
        $season = substr(findSeason($serie),1);
        $episode = substr(findEpisode($serie),1);


        if($this->Series->findByFile($serie_path)->first()['file']!=$serie_path){
          // on push les data dans les tableaux
          array_push($series_path, $serie_path);
          array_push($series_name, $name);
          array_push($series_season, $season);
          array_push($series_episode, $episode);

          // RÃ©Ã©criture pour ne pas grabber toutes les infos Ã  chaque Ã©pisode
          // On utilise le rÃ©sultat prÃ©cÃ©dent, comme les Ã©pisode sont scannÃ©s Ã  la suite, cela limite les accÃ¨s Ã  l'api externe
          // Pour cela on compare le nom des sÃ©ries
          $this->loadModel('Config');
          $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];

          if ($name == $name_old) {

            // On rÃ©cup' les data
            $serie_info = getEpisodeInfo($info_old, $season, $episode, $serie_path, $path, $apikey);
          } else {
            // Sinon procÃ©dure classique, on grab toutes les data !
            $serie_info = getSerie($name, $season, $episode, $serie_path, $path, $apikey);
          }



          if (is_array($serie_info)){
            $serie_add = $this->Series->newEntity($serie_info, ['validate' => false]);
            $this->Series->save($serie_add);
            array_push($series_ok, 'OK');
            $name_old = $name;
            $info_old = $serie_info;
          } else {
            array_push($series_ok, 'Erreur');
          }



        }

      }
      if (count($serie_path)==0){
        $this->Flash->success(__("Aucun film Ã  indexer !"));
      }else{
        $this->Flash->success(__("Fin du scan !"));
      }

      $this->set('series_path', $series_path);
      $this->set('series_name', $series_name);
      $this->set('series_season', $series_season);
      $this->set('series_episode', $series_episode);
      $this->set('series_ok', $series_ok);



    }


    public function stream($id = null)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_serie')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $serie = $this->Series->get($id, [
            'contain' => []
        ]);
        $file=$path."/".$serie->file;
        $titre = $serie->titre.'-S'.$serie->season.'E'.$serie->episode;
        $serie->view += 1;
        $this->Series->save($serie);

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

    public function download($id = null)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_serie')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $serie = $this->Series->get($id, [
            'contain' => []
        ]);
        $file=$path."/".$serie->file;
        $serie->view += 1;
        $this->Series->save($serie);

        $this->response->header('Location', $file );

    }


    public function manualAdd($file='', $search='')
    {
      $this->loadModel('Config');

      $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];
      $tmdb = new TMDB($apikey, 'fr');
      $tmdb_en = new TMDB($apikey, 'en');

      $film = $this->Series->newEntity();
      $series = $tmdb->searchTVShow($search);
      $this->set('search', $search);

      // Si recherche
      if (null != $this->request->query('search') ){
        $series = $tmdb->searchTVShow($this->request->query('search'));
         $this->set('search', $this->request->query('search'));
     }


      // Si post !

    if (null != $this->request->data('tmdb') ) {



      $this->loadModel('Folders');
      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Series')->first();

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
          }

       $id_tmdb = $this->request->data('tmdb');
       $file =  str_replace('-slash-', '/', $file);
       $file =  str_replace('-dot-', '.', $file);
       $serie = $tmdb->getTVShow($id_tmdb);

       downloadImg($tmdb->getImageURL('w185') . $serie->getPoster(), $serie->getID());


       //stockage dans la bdd :
       $id_tmdb = $serie->getID();
       $titre = $tmdb_en->getTVShow($serie->getID())->getName();
       if ($titre == '') {
         $titre = $serie->getName();

       }

       $resume = $serie->getOverview();
       if ($resume == '') {
         $resume =$tmdb_en->getTVShow($serie->getID())->getOverview();
       }
       $genre = '';
       $real = '';
       $acteur = '';
       $note = $serie->getVoteAverage();
       $annee = $serie->getYear();
       $encours = $serie->getInProduction();
       $season = substr(findSeason($file),1);
       $episode = substr(findEpisode($file),1);
       $titre_episode = $tmdb_en->getEpisode($id_tmdb, $season, $episode)->getName();
       $file = $file;

       $def = getDef($path.'/'.$file);
       $lang = getLang($path.'/'.$file);
       $sub = getSub($path.'/'.$file);


       $array = array(
         'id_tmdb' => $id_tmdb,
         'titre' => $titre,
         'file' => $file,
         'annee' => $annee,
         'realisateur' => $real,
         'genre' => $genre,
         'resume' => $resume,
         'acteur' => $acteur,
         'note' => $note,
         'season' => $season,
         'episode' => $episode,
         'encours' => $encours,
         'titre_episode' => $titre_episode,
         'def' => $def,
         'audio' => $lang,
         'sub' => $sub);



           $serie_add = $this->Series->newEntity($array, ['validate' => false]);
           if ($this->Series->save($serie_add)) {
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


      foreach ($series as $serie) {

        $serie = $tmdb->getTVShow($serie->getID());
        array_push($id, $serie->getID());
        array_push($poster, $tmdb->getImageURL('w92') . $serie->getPoster());
        array_push($title, $serie->getName());
        array_push($year, $serie->getYear());

      }
      $this->set('id', $id);
      $this->set('poster', $poster);
      $this->set('title', $title);
      $this->set('year', $year);

    }

        $this->set('file',$file);

        $this->set(compact('film'));
        $this->set('_serialize', ['film']);
    }


    public function moderate($act='', $file=''){

        $file = str_replace('-dot-','.',$file);
        $this->loadModel('Folders');
        $this->loadModel('Rmwords');

        $this->loadModel('Config');

        $apikey = $this->Config->findByNom('tmdb_api_key')->first()['valeur'];
        $tmdb = new TMDB($apikey, 'fr');

        // on rÃ©cupÃ¨re les variables issues des autres controleurs
        $settings = $this->Folders->findByType('Serie_user')->first();
        $rm_start = explode(',', $this->Rmwords->findByEnd('0')->first()['words']);
        $rm_end = explode(',', $this->Rmwords->findByEnd('1')->first()['words']);
        $filetype = $settings['filetype'];
        $path = $settings['path'];


        $settings2 = $this->Folders->findByType('Serie_upload')->first();
        $path2 = $settings2['path'];

        if ($act == 'remove') {
            unlink($path.'/'.$file);
            $this->Flash->warning(__("SÃ©rie rejetÃ©e !"));

        }

        if ($act == 'accept') {
            symlink($path.'/'.$file, $path2.'/'.$file);
            $this->Flash->success(__("SÃ©rie dÃ©placÃ©e vers Serie_Upload !"));

        }


        $dir = new Folder($path);

        $series_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
        // on initialise les tableaux
        $series_file = array();
        $series_name = array();
        $series_episode = array();
        $series_season = array();
        $series_def = array();
        $series_audio = array();
        $series_sub = array();
        $series_size = array();
        $series_exist = array();


        foreach ($series_original as $serie) {
          $def = getDef($serie);
          $audio = getLang($serie);
          $sub = getSub($serie);
          $size = size($serie);
          $serie = str_replace($path, '', $serie);
          $serie = str_replace('/', '', $serie);
          $serie_file = $serie;
          $name = rm_words($serie, $rm_end, $rm_start, '1');
          $season = substr(findSeason($serie),1);
          $episode = substr(findEpisode($serie),1);


          // On cherche si l'episode existe dÃ©jÃ 

          $id_tmdb_search = $tmdb->searchTVShow($name)[0]->getID();
          $id_tmdb = $this->Series->findByIdTmdbAndEpisodeAndSeason($id_tmdb_search,$episode,$season)->first();

          if ($id_tmdb['episode']==$episode && $id_tmdb['season']==$season){
              $exist = $id_tmdb['id'];
            }else {
              $exist = false;
            }

            // On push les data dans les tableaux
            array_push($series_file, $serie_file);
            array_push($series_name, $name);
            array_push($series_season, $season);
            array_push($series_episode, $episode);
            array_push($series_def, $def);
            array_push($series_audio, $audio);
            array_push($series_sub, $sub);
            array_push($series_size, $size);
            array_push($series_exist, $exist);
          }

        if (count($series_file)==0){
          $this->Flash->success(__("Aucune sÃ©rie Ã  renomer !"));
        }else{
          $this->Flash->success(__("Fin du scan !"));
        }


        $this->set('series_file', $series_file);
        $this->set('series_name', $series_name);
        $this->set('series_season', $series_season);
        $this->set('series_episode', $series_episode);
        $this->set('series_def', $series_def);
        $this->set('series_sub', $series_sub);
        $this->set('series_size', $series_size);
        $this->set('series_audio', $series_audio);
        $this->set('series_exist', $series_exist);


    }

    public function moderateStream($file, $titre)
    {
      $this->loadModel('Config');

      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Config->findByNom('url_serie_mod')->first();

      if ($settings) {
          $path = $settings['valeur'];
      } else {
          $path = false;
      }

        $file=$path."/".$file;

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


}
