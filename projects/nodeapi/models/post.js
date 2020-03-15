const mongoose = require('mongoose');
const Schema   = mongoose.Schema;
const {ObjectId} = mongoose.Schema;

const postSchema  = new Schema({
  title:{
    type:String,
    required:true
  },
  body:{
    type:String,
    required:true
  },
  photo:{
    type:Buffer,
    contentType:String
  },
  postedBy:{
    type:ObjectId,
    ref:"User"
  },
  created:{
    type:Date,
    default:Date.now
  }
});

module.exports = mongoose.model('Post',postSchema);
