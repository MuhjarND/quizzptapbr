<?php
// Pack transparent padding and encode a sharp, lightweight header derivative.
// Original artwork is retained for download and future brand assets.
$root=dirname(__DIR__);
$sourcePath=$argv[1] ?? $root.'/public/images/quiz-pta-pabar-logo-original.png';
$image=imagecreatefrompng($sourcePath);$w=imagesx($image);$h=imagesy($image);
$left=$w;$top=$h;$right=0;$bottom=0;
for($y=0;$y<$h;$y++)for($x=0;$x<$w;$x++){
    if(((imagecolorat($image,$x,$y)>>24)&127)<120){$left=min($left,$x);$top=min($top,$y);$right=max($right,$x);$bottom=max($bottom,$y);}
}
if($left>$right)throw new RuntimeException('Logo contains no visible artwork.');
$pad=12;$sw=$right-$left+1;$sh=$bottom-$top+1;$width=900;$height=(int)round(($sh+2*$pad)*$width/($sw+2*$pad));
$target=imagecreatetruecolor($width,$height);imagealphablending($target,false);imagesavealpha($target,true);imagefill($target,0,0,imagecolorallocatealpha($target,0,0,0,127));
$scale=$width/($sw+2*$pad);
imagecopyresampled($target,$image,(int)round($pad*$scale),(int)round($pad*$scale),$left,$top,(int)round($sw*$scale),(int)round($sh*$scale),$sw,$sh);
$output=$root.'/public/images/quiz-pta-pabar-logo.png';imagepng($target,$output,9);imagedestroy($image);imagedestroy($target);
echo 'Header logo: '.$width.' x '.$height.' ('.filesize($output)." bytes)\n";
