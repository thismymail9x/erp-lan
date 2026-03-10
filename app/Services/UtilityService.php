<?php

namespace App\Services;

use App\Models\SystemSettingModel;
use CodeIgniter\I18n\Time;

/**
 * Service xử lý các tiện ích hệ thống (vd: Quotes)
 * @package App\Services
 */
class UtilityService extends BaseService
{
    private $model;
    private $quotes = [
        "Công việc không thể chỉ là nhiệm vụ, nó phải là sự đam mê.",
        "Làm việc chăm chỉ là chìa khóa để mở cánh cửa thành công.",
        "Để thành công, bạn phải chấp nhận những rủi ro không thể đoán trước được.",
        "Không có con đường tắt đến thành công, chỉ có con đường chăm chỉ và kiên trì.",
        "Nếu bạn không thay đổi, bạn không thể mong đợi thay đổi.",
        "Mỗi ngày là cơ hội để cải thiện bản thân và công việc của bạn.",
        "Điều quan trọng không phải là bạn bắt đầu từ đâu, mà là bạn đi đến đâu.",
        "Người thành công là người biết biến thất bại thành cơ hội.",
        "Chỉ cần kiên trì, mọi khó khăn đều có thể vượt qua.",
        "Sự khác biệt giữa người thành công và người thất bại là sự kiên nhẫn và quyết tâm.",
        "Sự nỗ lực không bao giờ bị lãng phí, ngay cả khi kết quả không như mong đợi.",
        "Thành công không đến từ những gì bạn làm một lần, mà từ những gì bạn làm liên tục.",
        "Khi bạn đặt ra mục tiêu, bạn đã tạo ra con đường để đạt được chúng.",
        "Chỉ có những người dám mơ ước mới có thể biến ước mơ thành hiện thực.",
        "Khó khăn là cơ hội để bạn chứng minh khả năng và sức mạnh của chính mình.",
        "Trong mọi khó khăn, luôn có một bài học quý giá để học hỏi và trưởng thành.",
        "Sự bền bỉ trong những lúc khó khăn chính là chìa khóa mở cửa thành công.",
        "Khó khăn không phải là điểm dừng, mà là cơ hội để bắt đầu lại với một cách nhìn mới.",
        "Mỗi lần bạn vượt qua khó khăn, bạn không chỉ trở nên mạnh mẽ hơn mà còn thông thái hơn.",
        "Không có khó khăn nào là vĩnh viễn; mọi thứ đều sẽ qua đi nếu bạn kiên trì.",
        "Đối mặt với khó khăn là cách để bạn rèn luyện tính kiên cường và quyết tâm.",
        "Khó khăn là cách mà cuộc sống kiểm tra sự kiên nhẫn và lòng dũng cảm của bạn.",
        "Thành công không đến từ việc không bao giờ gặp khó khăn, mà từ việc bạn đối mặt và vượt qua chúng.",
        "Khó khăn chỉ làm bạn mạnh mẽ hơn nếu bạn không từ bỏ và tiếp tục chiến đấu.",
        "Khi bạn cảm thấy như đang đứng trên bờ vực, hãy nhớ rằng mọi khó khăn đều có thể được vượt qua với sự kiên nhẫn và quyết tâm.",
        "Đừng bao giờ bỏ cuộc khi gặp khó khăn, vì thành công thường đến ngay sau những thử thách lớn nhất.",
        "Nếu bạn không thể làm việc với niềm vui, ít nhất hãy làm việc với nụ cười!",
        "Đôi khi, tất cả những gì bạn cần là một ly cà phê và sự quyết tâm.",
        "Đừng để công việc làm bạn căng thẳng quá mức; hãy để nó làm bạn cười nhiều hơn.",
        "Cuộc đời quá ngắn để làm việc mà không có chút hài hước nào.",
        "Tôi không ngại khó khăn, tôi chỉ ngại không có gì khó khăn.",
        "Công việc không phải là cơn ác mộng, mà là một cuộc phiêu lưu hài hước khi bạn nhìn nó theo cách khác.",
        "Làm việc chăm chỉ và vui vẻ - đó là công thức cho một ngày làm việc thành công!",
        "Đôi khi, một ngày làm việc tồi tệ có thể được cứu vãn bằng một câu đùa vui.",
        "Công việc có thể mệt mỏi, nhưng hãy luôn nhớ rằng tiếng cười là thuốc bổ cho tinh thần.",
        "Đôi khi, một nụ cười tươi sáng là tất cả những gì bạn cần để vượt qua một ngày làm việc khó khăn.",
        "Làm việc như bạn đang tham gia một trò chơi vui nhộn và chắc chắn bạn sẽ thắng!",
        "Sáng tạo là khi bạn vẫn còn biết mơ mộng ngay cả khi đang làm việc.",
        "Sáng tạo là biết cách làm việc hiệu quả trong khi vẫn ngáp ngủ.",
        "Hãy làm việc cho đến khi tài khoản ngân hàng của bạn trông giống như số điện thoại của bạn.",
        "Chậm mà chắc vẫn tốt hơn là nhanh mà phải làm lại.",
        "Hãy làm việc như bạn không cần tiền, yêu như bạn chưa từng bị tổn thương, và nhảy múa như không có ai đang nhìn."
    ];

    public function __construct()
    {
        $this->model = new SystemSettingModel();
    }

    /**
     * Lấy câu nói hay hiện tại (xoay vòng mỗi 3 ngày)
     */
    public function getTodayQuote()
    {
        $stateRow = $this->model->find('quote_state');
        $state = json_decode($stateRow['value'], true);

        $now = Time::now();
        $lastUpdated = Time::parse($state['last_updated_at'] ?? '2000-01-01');

        // Kiểm tra xem đã đến lúc đổi quote chưa (3 ngày)
        $diff = $now->getTimestamp() - $lastUpdated->getTimestamp();
        $threeDays = 3 * 24 * 60 * 60;

        if ($diff >= $threeDays || empty($state['shuffled_indices'])) {
            // Cập nhật quote mới
            $state = $this->rotateQuote($state);
            $this->model->update('quote_state', ['value' => json_encode($state)]);
        }

        $currentIndexInShuffled = $state['current_index'];
        $quoteIndex = $state['shuffled_indices'][$currentIndexInShuffled];

        return $this->quotes[$quoteIndex] ?? $this->quotes[0];
    }

    /**
     * Xoay vòng quote
     */
    private function rotateQuote($state)
    {
        $count = count($this->quotes);
        
        // Nếu danh sách shuffled trống hoặc đã chạy hết vòng, tạo vòng mới
        if (empty($state['shuffled_indices']) || $state['current_index'] >= $count - 1) {
            $indices = range(0, $count - 1);
            shuffle($indices);
            $state['shuffled_indices'] = $indices;
            $state['current_index'] = 0;
        } else {
            $state['current_index']++;
        }

        $state['last_updated_at'] = Time::now()->toDateTimeString();

        return $state;
    }
}
