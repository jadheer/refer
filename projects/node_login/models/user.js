const mongoose      = require('mongoose');

const UserSchema    = new mongoose.Schema({
    _id:mongoose.Schema.Types.ObjectId,
    name  : {
        type : String,
        required : true
    },
    email  : {
        type : String,
        required : true
    },
    address:{
        type:String,
        required : true
    },
    password : {
        type : String,
        required : true
    },
    // phone_number  : {
    //     type : String,
    //     required : true
    // }
});


module.exports  = mongoose.model('users',UserSchema);
