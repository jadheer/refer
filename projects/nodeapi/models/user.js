const mongoose = require('mongoose');
const Schema   = mongoose.Schema;
const crypto = require('crypto');

const uuidv1  = require('uuid/v1');

const userSchema  = new Schema({
  name:{
    type:String,
    trim:true,
    required:true
  },
  email:{
    type:String,
    trim:true,
    required:true
  },
  hashed_password:{
    type:String,
    required:true
  },
  salt:{
    type:String
  },
  created:{
    type:Date,
    default:Date.now
  },
  updated:{
    type:Date
  }
});

// Virtual Field
userSchema.virtual('password')
.set(function(password){
    //Set temporary variable
    this._password = password;
    //Generate a timestamp
    this.salt = uuidv1();
    // Encrypt password
    this.hashed_password = this.encryptPassword(password);
})
.get(function(){
    return this._password;
})

// Methods
userSchema.methods = {

    authenticate  : function(plainText){
      return this.encryptPassword(plainText) == this.hashed_password
    },

    encryptPassword : function(password){
        if(!password) return "";
        try{
            return crypto.createHmac('sha1', this.salt)
                   .update(password)
                   .digest('hex');
        }
        catch{
            return "";
        }
    }
}

module.exports = mongoose.model('User',userSchema);
