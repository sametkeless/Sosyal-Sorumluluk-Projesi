<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;

class KullaniciController extends Controller
{
    public function __construct()
    {
    }

    //kullanıcı kayıt işlemleri fonksiyonu
    public function kayit(Request $request)
    {
      //Dooğrulama İşlemleri
      $this->validate($request, [
        'adi_soyadi' => 'required',
        'dogum_tarihi' => 'required|date_format:Y/m/d',
        'memleket_id' => 'required|numeric|min:1|max:81',
        'sifre' => 'required|min:8|max:12',
        'mail' => 'required|email|unique:kullanici',
        'telefon' => 'required|numeric',
        'resim' => 'image',
      ]);

      //Resim Upload İşlemleri
      if($request->file('resim'))
      {
        $image_manager = new ImageManager();
        $image = $request->file('resim');
        $filename  = uniqid().'.'.$image->getClientOriginalExtension();
        $path = \base_path("/public/resim/{$filename}");
        $image_manager->make($image->getRealPath())->resize(200, 200)->save($path);
        $resim = $filename;
      }

      //Veritabanına Kayıt İşlemi
      $kullanici_id = DB::table('kullanici')->insertGetId([
        'adi_soyadi' => $request->input('adi_soyadi'),
        'dogum_tarihi' => $request->input('dogum_tarihi'),
        'memleket_id' => $request->input('memleket_id'),
        'sifre' => $request->input('sifre'),
        'mail' => $request->input('mail'),
        'telefon' => $request->input('telefon'),
        'resim' => (isset($resim)) ? $resim : Null,
      ]);

      //Oturum İçin Token İşlemi
      $token = uniqid();
      DB::table('oturum')->insert([
        'kullanici_id' => $kullanici_id,
        'token_string' => $token,
      ]);

      //Sonuç İşlemleri
      return response()->json([
        'status' => 200,
        'message' => 'Kayıt başarılı!',
        'token' => $token,
        'kullanici' => [
          'adi_soyadi' => $request->input('adi_soyadi'),
          'dogum_tarihi' => $request->input('dogum_tarihi'),
          'memleket_id' => $request->input('memleket_id'),
          'sifre' => $request->input('sifre'),
          'mail' => $request->input('mail'),
          'telefon' => $request->input('telefon'),
          'resim' => (isset($resim)) ? url("/resim/{$resim}") : Null,
        ]
      ]);
    }

    //kullanıcı giriş işlemleri fonksiyonu
    public function giris(Request $request)
    {
      //Doğrulama İşlemi
      $this->validate($request ,[
        'mail' => 'required|email',
        'sifre' => 'required',
      ]);

      //Veritabanı Sorgulama İşlemi
      $user = DB::table('kullanici')->where([
        ['mail', '=', $request->input('mail')],
        ['sifre', '=', $request->input('sifre')],
      ])->first();

      //Giriş Doğrulanamıyorsa Aşağıdaki Sorgu Döndürülecek.
      if(empty($user)){
        return response()->json([
          'status' => 401,
          'message' => 'Giriş Başarısız. Lütfen tekrar deneyiniz.'
        ]);
      }

      //Oturum İçin Token İşlemi
      $token = uniqid();
      DB::table('oturum')->insert([
        'kullanici_id' => $user->kullanici_id,
        'token_string' => $token,
      ]);

      //Sonuç İşlemleri
      return response()->json([
        'status' => 200,
        'message' => 'Giriş Başarılı',
        'token' => $token,
        'kullanıcı' => $user,
      ]);
    }

    //kullanıcı profil güncelleme fonksiyonu
    public function profil_guncelle()
    {
    }

    //kullanıcı şifremi unuttum fonksiyonu
    public function sifremi_unuttum()
    {
    }
}
