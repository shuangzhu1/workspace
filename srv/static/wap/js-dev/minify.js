/**
 * Created by Administrator on 2017/10/12.
 * 使用：
 *      1、安装uglifyjs模块，版本3.0^
 *      2、命令行切换到minify目录 static/wap/js-dev/minify.js
 *      3、执行 node minify.js <path>
 *          e.g. node minify.js app/app.user.js
 */
//引入内置模块
var fs = require('fs'),
    path = require('path'),
    crypto = require('crypto'),
    path = require('path');

//generate version code
var md5 = crypto.createHash('md5'),
    hashCode = md5.update('klgwl.com' + (new Date().getTime())).digest('hex');

//引入压缩模块 required 3.0^
var UglifyJS = require('uglify-js');
var workDir =  path.dirname(process.argv[1]);//当前脚本路径
var filename_source = path.join(workDir,process.argv[2]);
filename_dest = filename_source.replace('js-dev','js');//目的文件

//压缩选项
var option = {
        mangle: {
            eval:true,
            reserved: ['$','require','exports']
        }
    }

var code = UglifyJS.minify(fs.readFileSync(filename_source, "utf8").toString(),option).code;
fs.writeFileSync(filename_dest,code);

//输出处理信息
console.log('\x1B[32m%s\x1B[0m','**********处理完成**********');
console.log('\x1B[33m%s\x1B[0m',"源文件：",filename_source);
console.log('\x1B[33m%s\x1B[0m',"新文件：",filename_dest);
console.log('\x1B[31m%s\x1B[0m',"Version Code：" , hashCode);
