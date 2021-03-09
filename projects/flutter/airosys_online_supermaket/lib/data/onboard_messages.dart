class SliderModel {
  String imagePath;
  String title;
  String desc;

  SliderModel({this.imagePath, this.title, this.desc});

  void setImageAssetPath(String getImagePath) {
    imagePath = getImagePath;
  }

  void setTitle(String getTitle) {
    title = getTitle;
  }

  void setDesc(String getDesc) {
    desc = getDesc;
  }

  String getImageAssetPath() {
    return imagePath;
  }

  String getTitle() {
    return title;
  }

  String getDesc() {
    return desc;
  }
}

List<SliderModel> getSlides() {
  List<SliderModel> slides = new List<SliderModel>();
  SliderModel sliderModel = new SliderModel();
  sliderModel.setImageAssetPath('assets/images/nine.png');
  sliderModel.setDesc('All needs in your hands');
  sliderModel.setTitle('Welcome to your personal supermarket');
  slides.add(sliderModel);

  sliderModel = new SliderModel();
  sliderModel.setImageAssetPath('assets/images/nine.png');
  sliderModel.setDesc('All needs in your hands2');
  sliderModel.setTitle('Welcome to your personal supermarket2');
  slides.add(sliderModel);

  sliderModel = new SliderModel();
  sliderModel.setImageAssetPath('assets/images/nine.png');
  sliderModel.setDesc('All needs in your hands3');
  sliderModel.setTitle('Welcome to your personal supermarket3');
  slides.add(sliderModel);
  return slides;
}
