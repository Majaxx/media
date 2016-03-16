<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
require_once (ROOT .DS."webroot/funct_music.php");

/**
 * Music Controller
 *
 * @property \App\Model\Table\MusicTable $Music
 */
class MusicController extends AppController
{

  public function indexUser()
  {
    $this->viewBuilder()->layout('music');
    $this->set('view', 'indexAlbum');
  }

  public function error()
  {
    $this->loadModel('Folders');


  }


  public function search()
  {
    // Pour la liste de recherche
      $annees = $this->Music->find()->select(['year'])->distinct('year')->order(['year' => 'DESC']);
      $years = array();
      foreach ($annees as $annee) {
        $year = substr($annee['year'],0,4);
        if (!in_array($year, $years)) {
          array_push($years,$year);
        }
      }
      $this->set('annees', $years);
    $this->viewBuilder()->layout('music_index');
    $year = $this->request->query('year');
    $search = $this->request->query('search');
    if (isset($search) && !isset($year)){
      $musics = $this->paginate($this->Music->find('all')->where([1,'OR' => ['album LIKE' => '%'.$search.'%', 'artist LIKE' => '%'.$search.'%', 'song LIKE' => '%'.$search.'%']])->distinct('artist'));
    }
    if (!isset($search) && isset($year)){
      $musics = $this->paginate($this->Music->find('all')->where(['year' => $year])->distinct('artist'));
    }
    if (isset($search) && isset($year)){
      $musics = $this->paginate($this->Music->find('all')->where([1, 'OR' => ['album LIKE' => '%'.$search.'%', 'artist LIKE' => '%'.$search.'%', 'song LIKE' => '%'.$search.'%']])->distinct('artist'));
    }

      $this->set('search', $search);
      $this->set('year', $year);
      $this->set('musics', $musics);
      $this->set('_serialize', ['music']);

  }

  public function indexView()
  {
    $this->paginate = ['limit' => 5, 'order' => ['Music.created' => 'desc']];
    $this->viewBuilder()->layout('music_index');

    if(null != $this->request->query('search') || null != $this->request->query('year')){
      $year = $this->request->query('year');
      $search = $this->request->query('search');
      $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
    }


    $artists = $this->Music->find('all')->distinct('artist');
    $albums = $this->Music->find('all')->distinct('album');
    $this->set('artists', $this->Paginate($artists));
    $this->set('albums', $albums);

    // Pour la liste de recherche
      $annees = $this->Music->find()->select(['year'])->distinct('year')->order(['year' => 'DESC']);
      $years = array();
      foreach ($annees as $annee) {
        $year = substr($annee['year'],0,4);
        if (!in_array($year, $years)) {
          array_push($years,$year);
        }
      }
      $this->set('annees', $years);

  }

  public function indexAlbum()
  {
    if(null != $this->request->query('search') || null != $this->request->query('year')){
      $year = $this->request->query('year');
      $search = $this->request->query('search');
      $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
    }

    $this->paginate = ['limit' => 20, 'order' => ['Music.created' => 'desc']];
    $this->viewBuilder()->layout('music_index');
    $albums = $this->Music->find('all')->distinct('album');
    $this->set('albums', $this->Paginate($albums));

    // Pour la liste de recherche
      $annees = $this->Music->find()->select(['year'])->distinct('year')->order(['year' => 'DESC']);
      $years = array();
      foreach ($annees as $annee) {
        $year = substr($annee['year'],0,4);
        if (!in_array($year, $years)) {
          array_push($years,$year);
        }
      }
      $this->set('annees', $years);

}

public function indexArtist()
{
  if(null != $this->request->query('search') || null != $this->request->query('year')){
    $year = $this->request->query('year');
    $search = $this->request->query('search');
    $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
  }

  $this->paginate = ['limit' => 20, 'order' => ['Music.created' => 'desc']];
  $this->viewBuilder()->layout('music_index');
  $artists = $this->Music->find('all')->distinct('artist');
  $this->set('artists', $this->Paginate($artists));

  // Pour la liste de recherche
    $annees = $this->Music->find()->select(['year'])->distinct('year')->order(['year' => 'DESC']);
    $years = array();
    foreach ($annees as $annee) {
      $year = substr($annee['year'],0,4);
      if (!in_array($year, $years)) {
        array_push($years,$year);
      }
    }
    $this->set('annees', $years);

}


  public function album($album='')
  {
    if(null != $this->request->query('search') || null != $this->request->query('year')){
      $year = $this->request->query('year');
      $search = $this->request->query('search');
      $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
    }

    $this->loadModel('Config');
    $this->loadModel('Folders');

    // on rÃ©cupÃ¨re les variables issues des autres controleurs
    $url = $this->Config->findByNom('url_music')->first()['valeur'];
    $path = $this->Folders->findByType('Musique')->first()['path'];

    $this->viewBuilder()->layout('music_album');
    $this->set('musics', $this->Music->findByAlbum($album)->order(['num'])
    );
    $this->set('url', $url);
    $this->set('path', $path);
  }

  public function viewArtist($artist='')
  {
    if(null != $this->request->query('search') || null != $this->request->query('year')){
      $year = $this->request->query('year');
      $search = $this->request->query('search');
      $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
    }
    $this->viewBuilder()->layout('music_index');
    $artist = $this->Music->findByArtist($artist)->distinct('artist')->first()['artist'];
    $albums = $this->Music->findByArtist($artist)->distinct('album');
    $this->set('artist',$artist);
    $this->set('albums', $albums);
    $this->set('refer', $this->referer());

    // Pour la liste de recherche
      $annees = $this->Music->find()->select(['year'])->distinct('year')->order(['year' => 'DESC']);
      $years = array();
      foreach ($annees as $annee) {
        $year = substr($annee['year'],0,4);
        if (!in_array($year, $years)) {
          array_push($years,$year);
        }
      }
      $this->set('annees', $years);

  }

  public function viewAlbum($album='')
  {
    if(null != $this->request->query('search') || null != $this->request->query('year')){
      $year = $this->request->query('year');
      $search = $this->request->query('search');
      $this->redirect(['action' => 'search', '?' => ['search' => $search, 'year'=>$year]]);
    }
    $this->loadModel('Config');
    $this->loadModel('Folders');

    // on rÃ©cupÃ¨re les variables issues des autres controleurs
    $url = $this->Config->findByNom('url_music')->first()['valeur'];
    $path = $this->Folders->findByType('Musique')->first()['path'];

    $this->viewBuilder()->layout('music_index');
    $this->set('musics', $this->Music->findByAlbum($album)->order(['num'])
    );
    $this->set('url', $url);
    $this->set('path', $path);
    $this->set('refer', $this->referer());

  }


    public function indexAdmin()
    {
        $this->set('music', $this->paginate($this->Music));
        $this->set('_serialize', ['music']);
    }

    /**
     * View method
     *
     * @param string|null $id Music id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $music = $this->Music->get($id, [
            'contain' => []
        ]);
        $this->set('music', $music);
        $this->set('_serialize', ['music']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $music = $this->Music->newEntity();
        if ($this->request->is('post')) {
            $music = $this->Music->patchEntity($music, $this->request->data);
            if ($this->Music->save($music)) {
                $this->Flash->success(__('The music has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The music could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('music'));
        $this->set('_serialize', ['music']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Music id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $music = $this->Music->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $music = $this->Music->patchEntity($music, $this->request->data);
            if ($this->Music->save($music)) {
                $this->Flash->success(__('The music has been saved.'));
                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The music could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('music'));
        $this->set('_serialize', ['music']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Music id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $music = $this->Music->get($id);
        if ($this->Music->delete($music)) {
            $this->Flash->success(__('The music has been deleted.'));
        } else {
            $this->Flash->error(__('The music could not be deleted. Please, try again.'));
        }
        return $this->redirect(['action' => 'index']);
    }

    /////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////
    ///////////////////////////////                         //////////////////
    //////////////////////////////                          /////////////////
    ////////////////////////////////////////////////////////////////////////

    public function import(){


      $this->loadModel('Folders');


      // on rÃ©cupÃ¨re les variables issues des autres controleurs
      $settings = $this->Folders->findByType('Musique')->first();

      $filetype = $settings['filetype'];

      if ($settings) {
          $path = $settings['path'];
      } else {
          $path = false;
          $this->Session->setFlash(__('Veuillez configurer un dossier pour la musique.'), 'flash_error');
          $this->redirect(array('controller' => 'settings', 'action' => 'index'));
      }
      $dir = new Folder($path);


      $musiques_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
      // on initialise les tableaux
      $musiques_title = array();
      $musiques_num = array();
      $musiques_album = array();
      $musiques_artist = array();
      $musiques_path = array();
      $musiques_ok = array();

      foreach ($musiques_original as $music) {

        $music_path = str_replace($path.'/', '', $music);
        if($this->Music->findByFile($music_path)->first()['file']!=$music_path){
        $musicInfo = getInfo($music, $music_path);
          if (is_array($musicInfo) && $musicInfo['song']!=''){

            // on push les data dans les tableaux
            array_push($musiques_title, $musicInfo['song']);
            array_push($musiques_num, $musicInfo['num']);
            array_push($musiques_album, $musicInfo['album']);
            array_push($musiques_artist, $musicInfo['artist']);
            array_push($musiques_path, $music_path);

            $music_add = $this->Music->newEntity($musicInfo, ['validate' => false]);
            $this->Music->save($music_add);
            array_push($musiques_ok, 'OK');
          } else {
            array_push($musiques_ok, 'Erreur');
          }

      }
    }

      if (count($musiques_path)==0){
        $this->Flash->success(__("Aucune musique Ã  indexer !"));
      }else{
        $this->Flash->success(__("Fin du scan !"));
      }


      $this->set('musiques_title', $musiques_title);
      $this->set('musiques_num', $musiques_num);
      $this->set('musiques_album', $musiques_album);
      $this->set('musiques_artist', $musiques_artist);
      $this->set('musiques_path', $musiques_path);
      $this->set('musiques_ok', $musiques_ok);




    }

public function download($folder='', $file='')
{
  $folder = str_replace('-slash-', '/', $folder);

      Zip($folder,$file);
}


public function upload()
{
  //Pas d'upload pour l'instant !

  $this->loadModel('Folders');
  // on rÃ©cupÃ¨re les variables issues des autres controleurs
  $path = $this->Folders->findByType('Musique_upload')->first()['path'];
  $this->set('upload_dir', $path);

}

public function rename(){

    $this->loadModel('Folders');

    // on rÃ©cupÃ¨re les variables issues des autres controleurs
    $settings = $this->Folders->findByType('Musique')->first();

    $filetype = $settings['filetype'];

    if ($settings) {
        $path = $settings['path'];
    } else {
        $path = false;
        $this->Session->setFlash(__('Veuillez configurer un dossier pour la musique.'), 'flash_error');
        $this->redirect(array('controller' => 'settings', 'action' => 'index'));
    }

    $settings2 = $this->Folders->findByType('Musique_upload')->first();
    if ($settings2) {
        $path2 = $settings2['path'];
    } else {
        $path2 = false;
        $this->Session->setFlash(__('Veuillez configurer un dossier pour la Musique.'), 'flash_error');
        $this->redirect(array('controller' => 'settings', 'action' => 'index'));
    }

    $dir = new Folder($path2);

    $musics_original = $dir->findRecursive('^.*\.('.str_replace(',', '|', $filetype).')$');
    // on initialise les tableaux
    $musics_song = array();
    $musics_album = array();
    $musics_year = array();
    $musics_artist = array();
    $musics_num = array();
    $musics_ext = array();
    $musics_move = array();
    $musics_path = array();
    $musics_ok = array();

    foreach ($musics_original as $music) {
      $music_path = $music;
      $music = str_replace($path2.'/', '', $music);
      $musicInfo = getInfo($music_path, $music_path);
      $ext = getExt($music_path);
      $year = $musicInfo['year'];
      $song = $musicInfo['song'];
      $album = $musicInfo['album'];
      $artist = $musicInfo['artist'];
      $num = $musicInfo['num'];

      $move = movePath($path, $artist, $album, $year, $num, $song, $ext);

      if($move != $music_path){
        // on push les data dans les tableaux
        array_push($musics_path, $music_path);
        array_push($musics_song, $song);
        array_push($musics_year, $year);
        array_push($musics_ext, $ext);
        array_push($musics_album, $album);
        array_push($musics_artist, $artist);
        array_push($musics_num, $num);
        array_push($musics_move, $move);
        // On crÃ©Ã© le fichier avant > permet de crÃ©er les dossiers 20XX
        $file_move = new File ($move, true, 0777);
		unlink($move);
        // on move ! avec symlink !
        if(symlink($music_path, $move)){
          array_push($musics_ok, 'OK');
          //chmod($move, 0777);
        }else {
          array_push($musics_ok, 'Erreur');
        }

      }

    }
    if (count($musics_path)==0){
      $this->Flash->success(__("Aucun film Ã  renomer !"));
    }else{
      $this->Flash->success(__("Fin du scan !"));
    }

    $this->set('musics_path', $musics_path);
    $this->set('musics_song', $musics_song);
    $this->set('musics_year', $musics_year);
    $this->set('musics_ext', $musics_ext);
    $this->set('musics_move', $musics_move);
    $this->set('musics_num', $musics_num);
    $this->set('musics_album', $musics_album);
    $this->set('musics_artist', $musics_artist);


    $this->set('musics_ok', $musics_ok);
}


}
