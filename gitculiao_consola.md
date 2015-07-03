 Primero que todo; me costo mas que la mierda poder subir un puto archivo, los programas con GUI pa GIT en linux no me sirvieron (posiblemente debido a una  mala configuracion
   del mismo).
 Luego de una exhaustiva busqueda por los rincones de san google encontré un tutorial, en contré varios en realidad, de los cuales no se hace uno.
 Faltaban un monton de cosas, tipo que hay que situarse en la carpeta que se quiere usar como repo en GIT, o que hay que hacer efecutar ciertos  procedimientos, etc, etc, etc.


 No me la ganaste git reculiao :v


 Ahora; lo bonito y lo importante es como se usa (no pa que sirve y que mierda es lo que es; la cosa es simple Git es un sistema de monitoreo de paquetes,  presumiblemente codigo, el cual se utiliza dentro de consola en linux; y hay un monton de GUI pa mac y wilson.

____________________________________________________________________________________________________________________________________________

#                                                                                                ME CAGASTE LA VIDA GIT CULIAO


 En linux instalar git
   ~~~
   apt-get git-all
   ~~~

   (el paquete de mierda baja una sarta de cosas que SIRVEN)


~~~
  $ mkdir /home/(usuario))/repo
   $ cd /home/(usuario))/repo
   $ git init  <--inicializar repositorio de git-->>
   $ git clone github.com/usuario/repo <--clona archivos del repositorio de git en carpeta local-->>
~~~

git va a pedir nombre de usuario y password y procederá con la clonacion de (tarjetas)




 La estructura de archivos en git es tiene 3 etapas, porque es shuper, onda _shuper_.
 Tenemos el repo de trabajo, el index y el HEAD, el cual apunta al ultimo cambio que se hizo.


 Se pueden agregar archivos de dos maneras   (en terminal con situandose dentro de la carpeta del repo) con
~~~  
$ git add <nombre de archivo> <--para agregarlos uno por uno-->
~~~


  o


~~~
$ git add(espacio)(asterisco) <--para agregarlos todos-->
~~~


y se usa el comando


~~~
 $ git commit -m "mensaje je je je"
 ~~~

para identificar que se hizo con que archivo

Con esto se efectuan los cambios en el HEAD, pero no en el index, mintindi?

Los cambios etán en el HEAD, pero el weón no se ha pegado la avispá de que hay que subir los archivos al index,  asi que se le da un _empujoncito_ con el commando

~~~  
$ git push (github.com/usuario/repo) master
~~~

terminal va a entregar una serie de instrucciones tipo "_aguantame un metro que estoy subiendo estas cuestiones_"
y despues va a decir "Success!" y alguna mierda con que se han subido los archivos y la wea.

Pa cachar si de verdat verdat los archivos se subieron al repo se usa el comando

~~~
$git checkout master
~~~

 el cual lista los archivos que estan en el repo.
 Ahora si queríh la wea tambien, sino refrescai la wea de pagina del repo y verificai si si o si no.

 Si no aplicai un _if _ y empezai otra vez.
