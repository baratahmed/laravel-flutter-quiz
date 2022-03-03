<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Quizfeedback;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Attempt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;


class ApiController extends Controller
{
    public function qwerty(){
        return response()->json([
            'qwerty' => auth()->id(),
        ]);

        $user = $this->getAuthenticatedUser();
        return response()->json(compact('user'));
    }

    public function authenticate(Request $request){

        $credentials = $request->only('email','password');

        try {
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json(['error'=>'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error'=>'could_not_create_token'], 500);
        }
        
        $user = auth()->user();

        return response()->json(compact('token','user'));
        

    }

    public function getAuthenticatedUser(){
        try {
            if(!$user = JWTAuth::parseToken()->authenticate()){
                return response()->json(['user_not_found'], 400);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return $user;
        // return response()->json(compact('user'));


    }

    public function register(Request $request){
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(),400);
        }
        $user = new User;
        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->role_id = 2;
        $user->save();
        
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'), 201);

    }

    private function myscore($quid){
        return DB::table('answers')->join('attempts','attempts.aid','=','answers.id')
            ->select('*')
            ->where('attempts.quiz_id',$quid)
            ->where('answers.correct',1)
            ->where('attempts.user_id',auth()->id())
            ->count();
    } 

    private function ansx($id){ // quiz_id
        return DB::table('answers')->join('questions','questions.id','=','answers.question_id')->select('*')->where('quiz_id',$id)->count();
    }

    private function rankme($quid){
        $r = 1;
        foreach (User::where('role_id',2)->get() as $u) {
            $rank = DB::table('answers')->join('attempts','attempts.aid','=','answers.id')
            ->select('*')
            ->where('attempts.quiz_id',$quid)->where('answers.correct',1)
            ->where('attempts.user_id',$u->id)
            ->count();
            $ranks[$u->id] = $r;
            $r++;
        }
        asort($ranks);
        return $ranks[auth()->id()]."/".User::where('role_id',2)->count();
    }

    public function dashboard(){
        $quizzes = Quiz::where('status',1)->orderBy('id','DESC')->get();

        // return response()->json([
        //     "data" => count($quizzes)
        // ],200);

        if(count($quizzes)){
            $dataX = [];
            foreach ($quizzes as $quiz ) {
                $c2 = Attempt::where('quiz_id',$quiz->id)->where('user_id',auth()->id())->count();
                if($c2 == 0){
                    $data = [];
                    $data["quiz_id"] = $quiz->id;
                    $data["quiz_name"] = $quiz->quiz_name;
                    $data["description"] = $quiz->description;
                    $data["questions_no"] = $quiz->questions_no;
                    $data["result"] = [];
                    $dataX[] = $data;                    
                }
                else{
                $c = Quizfeedback::where('user_id',auth()->id())->where('quiz_id',$quiz->id)->get();
                    if(count($c) > 0){
                        $data = [];
                        $data["quiz_id"] = $quiz->id;
                        $data["quiz_name"] = $quiz->quiz_name;
                        $data["description"] = $quiz->description;
                        $data["questions_no"] = $quiz->questions_no;
                        $data["result"] = ["score"=>$this->myscore($quiz->id).'/'.$this->ansx($quiz->id)];
                        $dataX[] = $data;
                    }
                }
            }

            if(count($dataX) == 0){
                return response()->json([
                    "data" => ["quizzes" => []]
                ],200);
            }
            return response()->json([
                "data" => ["quizzes" => $dataX]
            ],200);
            
        }else{
            return response()->json([
                "data" => []
            ],200);
        }
    }

    public function startQuiz($id){
        $quiz = Quiz::find($id);
        if($quiz){
            if($quiz->status == 1){
                $questions = Question::where('quiz_id',$quiz->id)->orderBy('qn_no','ASC')->get();
                $questions_ = [];
                foreach ($questions as $q) {
                    $answers = Answer::where('question_id',$q->id)->get();
                    $data = [];
                    $data["question_id"] = $q->id;
                    $data["question_number"] = $q->qn_no;
                    $data["question_body"] = $q->question;
                    $data["question_category"] = $q->category;
                    $data["question_photo_location"] = $q->qn_photo_location;

                    $questions_answers = [];
                    foreach ($answers as $a) {
                        $attt = DB::table('answers')->join('attempts','attempts.aid','=','answers.id')
                                ->select('*')
                                ->where('attempts.quiz_id',$quiz->id)->where('answers.correct',1)
                                ->where('attempts.user_id',auth()->id())->where('answers.id',$a->id)
                                ->count();
                        if($attt){
                            $checked = true;
                        }else{
                            $checked = false;
                        }
                        $datax = [];
                        $datax["answer_id"] = $a->id;
                        $datax["my_answer"] = $checked;
                        $datax["answer"]    = $a->answer;
                        $datax["correct"]   = $a->correct == 0 ? false : true;
                        $questions_answers[] = $datax;

                        $data["questions_answers"] = $questions_answers;
                    }
                    
                    $questions_[] = $data;
                }
                return response()->json([
                    "data" => [
                        "quiz" => ["quiz"=>$quiz, "detail"=>$questions_],
                        "message" => null,
                        "error" => false,
                    ]
                ],200);
            }

            return response()->json([
                "data" => [
                    "quiz" => null,
                    "message" => "Quiz is not yet published!",
                    "error" => true,
                ]
            ],400);

        }

        return response()->json([
            "data" => [
                "quiz" => null,
                "message" => "No Quiz Found!",
                "error" => true,
            ]
        ],404);
    }

    public function myQuizzes(){
        $quizzes = DB::table('quizzes')->join('attempts','quizzes.id','=','attempts.quiz_id')->select('attempts.*','quizzes.*')->where('attempts.user_id',auth()->id())->where('quizzes.completed',1)->orderBy('quizzes.id','DESC')->get();
        $quiz_ids = [];
        $qz = [];
        foreach ($quizzes as $q) {
            $qid = $q->quiz_id;
            $data = [];
            if(!in_array($qid, $quiz_ids)){
                $quiz_ids = $qid;
                $data["quiz_name"] = $q->quiz_name;
                $data["quiz_id"] = $q->quiz_id;
                $data["questions_no"] = $q->questions_no;
                $data["description"] = $q->description;
                $data["result"] = ["score" => $this->myscore($q->id).'/'.$this->ansx($q->id), "rank"=> $this->rankMe($q->id)];
                $qz[] = $data;
            }
        }
        // return ["data" => $qz];

        return response()->json([
            "data" => $qz
        ],200);
        
    }

    public function attempt($qid){
        $user_id = auth()->id();
        $quiz_id = $qid;
        $check = Attempt::where('quiz_id',$quiz_id)->where('user_id',$user_id)->count();
        if($check){
            return response()->json([
                "error" => true,
                "msg" => "You already done this quiz.",

            ]);
        }
        $answers = (request('attempts'));
        if($answers){
            foreach ($answers as $a) {
                $aid = $a['answer_id'];
                $qid = $a['question_id'];
                $att = new Attempt;
                $att->qid = $qid;
                $att->aid = $aid;
                $att->user_id = $user_id;
                $att->quiz_id = $quiz_id;
                $att->save();
            }
        }else{
            $att = new Attempt;
            $att->user_id = $user_id;
            $att->quiz_id = $quiz_id;
            $att->save();
        }
        return response()->json([
            "error" => false,
            "msg" => "Successfully Submitted!",
        ]);
    }

    public function getAttempts($qid){
        $user_id = auth()->id();
        $quiz_id = $qid;
        return Attempt::where('quiz_id', $quiz_id)->where('user_id', $user_id)->get();
    }

    public function changePassword(){
        $cnewpassword = request('password');
        $user = User::find(auth()->id());
        $user->password = bcrypt($cnewpassword);
        $user->save();
        return response()->json([
            "message" => "Password was changed successfully!",
        ],200);
    }

    public function fullQuiz($qid){
        $quiz = Quiz::find($qid);
        if ($quiz) {
            if($quiz->status == 1){
                $questions = Question::where('quiz_id', $quiz->id)->orderBy('qn_no','ASC')->get();
                $questions_ = [];
                foreach ($questions as $q) {
                    $answers = Answer::where('question_id', $q->id)->get();
                    $data = [];
                    $data["question_id"] = $q->id;
                    $data["question_number"] = $q->qn_no;
                    $data["question_body"] = $q->question;
                    $data["question_category"] = $q->category;
                    $data["question_photo_location"] = $q->qn_photo_location;

                    $questions_answers = [];
                    foreach ($answers as $a) {
                        $attt = DB::table('answers')->join('attempts','attempts.aid','=','answers.id')
                                ->select('*')
                                ->where('attempts.quiz_id',$quiz->id)->where('answers.correct',1)
                                ->where('attempts.user_id',auth()->id())->where('answers.id',$a->id)
                                ->count();
                        if($attt){
                            $checked = true;
                        }else{
                            $checked = false;
                        }
                        $datax = [];
                        $datax["answer_id"] = $a->id;
                        $datax["my_answer"] = $checked;
                        $datax["answer"]    = $a->answer;
                        $datax["correct"]   = $a->correct == 0 ? false : true;
                        $questions_answers[] = $datax;

                        $data["questions_answers"] = $questions_answers;
                    }
                    $questions_[] = $data;
                }
                return response()->json([
                    "data" => [
                        "quiz" => ["quiz"=>$quiz, "detail"=>$questions_],
                        "message" => null,
                        "error" => false,
                    ]
                ],200);
            }
            return response()->json([
                "data" => [
                    "quiz" => null,
                    "message" => "Quiz is not yet published!",
                    "error" => true,
                ]
            ],400);
        }else{
            return response()->json([
                "data" => [
                    "quiz" => null,
                    "message" => "No Quiz Found!",
                    "error" => true,
                ]
            ],404);
        }
    }

    public function seenResults($id){
        $qfs = Quizfeedback::where('user_id',auth()->id())->where('quiz_id',$id)->get();
        if(count($qfs)){
            foreach ($qfs as $qf) {
                $qff = Quizfeedback::find($qf->id);
                $qff->seen = 1;
                $qff->save();
            }
        }
        return response()->json([
                "error" => false,
                "msg" => 'Successfully Updated',
        ]);
    }

    public function saveFCMToken(){
        $token = request('fcm_token');
        $check = FcmToken::where('fcm_token', $token)->count();
        if($check == 0){
            $fcmToken = new FcmToken;
            $fcmToken->fcm_token = $token;
            $fcmToken->save();
        }
        return response()->json([
            'msg' => 'Token saved successfully!!!'
        ],200);
    }

    public function logout(Request $request){
        return 'logout';
    }
}
